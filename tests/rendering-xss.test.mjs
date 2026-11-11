import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = path => readFileSync(new URL('../' + path, import.meta.url), 'utf8')
	.replace(/^import .*;\r?\n/gm, '').replace(/^export /gm, '');
const context = vm.createContext({ console });
vm.runInContext(source('scripts/modules/escapeHtml.js') + source('scripts/modules/ScriptRenderer.js'), context);
const payload = `<img src=x onerror="window.probed=true"> & ' </textarea><script>alert(1)</script><!--`;
context.payload = payload;
vm.runInContext(`
const el = { innerHTML: '', setAttribute() {} };
const panel = { scene: payload, action: payload, background: payload,
 dialog: [{ character: payload, text: payload }] };
const script = { title: payload, concept: payload, panels: [panel],
 story: { title: payload, currentIdx: payload },
 credits: { concept: payload, script: payload, image: payload } };
const renderer = new ScriptRenderer({ el });
renderer.script = script;
renderer.render();
globalThis.html = el.innerHTML;
globalThis.original = script.title;
globalThis.escaped = escapeHtml(payload);
`, context);
assert.equal(context.original, payload, 'Rendering must preserve the original stored text');
assert.ok(context.html.includes(context.escaped));
assert.ok(!context.html.includes('<img'));
assert.ok(!context.html.includes('<script'));
assert.ok(!context.html.includes('</textarea>'));
assert.ok(context.html.includes('&amp;'));
assert.equal(vm.runInContext('escapeHtml(null)', context), '');
console.log('Script rendering escapes hostile fields and preserves source text.');
