const fs = require("fs");
const path = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(path, "utf8");
s = s.replace(/async function dismissDialog[\s\S]*?\}\n\n/, `async function clickMatConfirm(page, name) {
  const dialog = page.locator('mat-dialog-container');
  await dialog.waitFor({ state: 'visible', timeout: 15000 });
  await page.getByRole('button', { name }).click();
  await dialog.waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
}

`);
s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');",
  "    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');\n    await clickMatConfirm(page, 'Continuar');");
s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();",
  "    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();\n    await clickMatConfirm(page, 'Aprovar');");
s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    await page.click('button:has-text(\"Pedir arquitectura\")');",
  "    await page.click('button:has-text(\"Pedir arquitectura\")');\n    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {\n      await clickMatConfirm(page, 'Continuar');\n    }");
fs.writeFileSync(path, s, "utf8");
console.log((s.match(/page\.once\('dialog'/g)||[]).length, (s.match(/function clickMatConfirm/g)||[]).length);
