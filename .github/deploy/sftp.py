"""Upload tracked website files over password-authenticated, host-pinned SFTP."""

import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile


def required(name):
    value = os.environ.get(name, "")
    if not value or any(ord(char) < 32 or ord(char) == 127 for char in value):
        raise ValueError(f"Set a nonempty, single-line {name} secret")
    return value


def quote(value):
    # lftp has its own command parser; never interpolate raw secret paths.
    return '"' + value.replace('\\', '\\\\').replace('"', '\\"').replace('$', '\\$').replace('`', '\\`') + '"'


def deploy():
    username = required("ZCG_LIVE_SFTP_USERNAME")
    password = required("ZCG_LIVE_SFTP_PASSWORD")
    host = required("ZCG_LIVE_SFTP_HOST")
    port = required("ZCG_LIVE_SFTP_PORT")
    source = required("ZCG_LIVE_SOURCE_PATH")
    remote = required("ZCG_LIVE_REMOTE_PATH")
    fingerprint = required("ZCG_LIVE_HOST_FINGERPRINT")
    if not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9.-]*", host):
        raise ValueError("SFTP host must be a hostname or IPv4 address, without scheme, port or path")
    if not port.isdecimal() or not 1 <= int(port) <= 65535:
        raise ValueError("SFTP port must be between 1 and 65535")
    if not re.fullmatch(r"[A-Za-z0-9_][A-Za-z0-9_.@-]*", username):
        raise ValueError("Invalid SFTP username")
    if not remote.startswith("/") or remote == "/" or ".." in Path(remote).parts:
        raise ValueError("Remote path must be an absolute existing site directory, not root")
    if not re.fullmatch(r"SHA256:[A-Za-z0-9+/]{43}", fingerprint):
        raise ValueError("Supply the verified SHA256 server host fingerprint")

    root = Path.cwd().resolve()
    local = (root / source).resolve()
    if not local.is_relative_to(root) or not local.is_dir():
        raise ValueError("Source path must be an existing directory inside the checkout")

    tracked = subprocess.check_output(["git", "ls-files", "-z"]).decode().split("\0")
    with tempfile.TemporaryDirectory(prefix="zcg-deploy-") as temporary:
        temporary = Path(temporary)
        stage = temporary / "site"
        stage.mkdir()
        for filename in filter(None, tracked):
            path = root / filename
            if not path.is_relative_to(local):
                continue
            relative = path.relative_to(local)
            # Keep repository metadata, private config, developer tools and
            # server-managed assets out of the public document root.
            if any(part.startswith(".") and part != ".htaccess" for part in relative.parts):
                continue
            if relative.parts[0] in {"docs", "tests", "vendor", "backup", "admin"}:
                continue
            if path.suffix.lower() in {".md", ".sql", ".pem", ".key", ".log"}:
                continue
            if relative.as_posix() in {"api/includes/key.php", "api/includes/key_example.php"}:
                continue
            if relative.parts[:3] == ("api", "comicpromoter", "tmp"):
                continue
            if path.is_symlink() or not path.is_file():
                raise ValueError("Deployment source contains a symlink or missing tracked file")
            target = stage / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(path, target)
        if not (stage / "index.php").is_file():
            raise ValueError("Source must contain the website index.php")

        hosts_file = temporary / "known_hosts"
        # Discover public server identity, then verify against the independently
        # supplied fingerprint before permitting a password to be sent.
        scanned = subprocess.run(
            ["ssh-keyscan", "-T", "15", "-p", port, host],
            check=True, capture_output=True, text=True,
        ).stdout
        verified = []
        for entry in scanned.splitlines():
            if not entry or entry.startswith("#"):
                continue
            hosts_file.write_text(entry + "\n")
            result = subprocess.run(
                ["ssh-keygen", "-l", "-E", "sha256", "-f", str(hosts_file)],
                check=True, capture_output=True, text=True,
            )
            if result.stdout.split()[1] == fingerprint:
                verified.append(entry)
        if not verified:
            raise ValueError("SFTP server identity does not match the trusted fingerprint")
        hosts_file.write_text("\n".join(verified) + "\n")
        hosts_file.chmod(0o600)
        ssh = ("ssh -a -x -o StrictHostKeyChecking=yes "
               f"-o UserKnownHostsFile={hosts_file} -o GlobalKnownHostsFile=/dev/null "
               "-o UpdateHostKeys=no -o ForwardAgent=no -o PubkeyAuthentication=no "
               "-o PreferredAuthentications=password -o NumberOfPasswordPrompts=1 "
               "-o ConnectTimeout=30")
        commands = "\n".join([
            "set cmd:fail-exit yes",
            "set sftp:auto-confirm no",
            f"set sftp:connect-program {quote(ssh)}",
            "set net:max-retries 2",
            "set net:timeout 30",
            "set xfer:use-temp-file yes",
            f"open --env-password --user {quote(username)} sftp://{host}:{int(port)}",
            f"cd {quote(remote)}",
            # No --delete: preserve runtime assets and server-only credentials.
            f"mirror --reverse --ignore-time --no-perms {quote(str(stage))} ./",
            "bye", "",
        ])
        environment = os.environ.copy()
        environment.pop("ZCG_LIVE_SFTP_PASSWORD", None)
        environment["LFTP_PASSWORD"] = password
        subprocess.run(["lftp"], input=commands, text=True, env=environment, check=True)


if __name__ == "__main__":
    deploy()
