const fs = require("fs");
const path = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let lines = fs.readFileSync(path, "utf8").split(/\r?\n/);
const start = lines.findIndex((l) => l.startsWith("async function dismissDialog"));
const end = lines.findIndex((l, i) => i > start && l === "}");
// find end of dismissDialog - next empty line after closing brace of inner function
let endIdx = start;
let depth = 0;
for (let i = start; i < lines.length; i++) {
  if (lines[i].includes("{")) depth++;
  if (lines[i].includes("}")) depth--;
  if (i > start && depth === 0) { endIdx = i; break; }
}
const insert = [
  "async function clickMatConfirm(page, name) {",
  "  const dialog = page.locator('mat-dialog-container');",
  "  await dialog.waitFor({ state: 'visible', timeout: 15000 });",
  "  await page.getByRole('button', { name }).click();",
  "  await dialog.waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});",
  "}",
];
lines.splice(start, endIdx - start + 1, ...insert);
let s = lines.join("\n");
s = s.replace(/\s*page\.once\('dialog', async \(d\) => await d\.accept\(\)\);\r?\n/g, "");
s = s.replace(
  "    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');",
  "    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');\n    await clickMatConfirm(page, 'Continuar');"
);
s = s.replace(
  "    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();",
  "    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();\n    await clickMatConfirm(page, 'Aprovar');"
);
s = s.replace(
  "    await page.click('button:has-text(\"Pedir arquitectura\")');",
  "    await page.click('button:has-text(\"Pedir arquitectura\")');\n    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {\n      await clickMatConfirm(page, 'Continuar');\n    }"
);
fs.writeFileSync(path, s, "utf8");
console.log("dialogs", (s.match(/page\.once\('dialog'/g)||[]).length, "fn", s.includes("clickMatConfirm"));
