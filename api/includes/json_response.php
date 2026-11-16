<?php
/**
 * Escape HTML-sensitive characters at the JSON response boundary.
 * Keep stored content unchanged, including existing comics. JSON parsers restore
 * the original text; consumers must still use textContent or HTML escaping when
 * rendering it. This also prevents literal closing script tags in responses.
 */
function encodeApiResponse($value) {
    return json_encode(
        $value,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            | JSON_INVALID_UTF8_SUBSTITUTE
    );
}
