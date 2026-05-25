const fs = require("fs");
const path = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(path, "utf8");

const helperOld = `async function dismissDialog(page, accept = true) {
  page.once('dialog', async (d) => {
    if (accept) await d.accept();
    else await d.dismiss();
  });
}`;
const helperNew = `async function clickMatConfirm(page, name) {
  const dialog = page.locator('mat-dialog-container');
  await dialog.waitFor({ state: 'visible', timeout: 15000 });
  await page.getByRole('button', { name }).click();
  await dialog.waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
}`;
if (s.includes(helperOld)) s = s.replace(helperOld, helperNew);

// step 3 block: from waitForSelector dashboard credits through log 3
const i3 = s.indexOf("    // 3 CTA");
const i4 = s.indexOf("    // 4 Wizard");
if (i3 === -1 || i4 === -1) throw new Error("markers");
const new3 = `    // 3 CTA estudio
    const tStudio = Date.now();
    await page.locator('app-customer-dashboard mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});
    await page.getByRole('link', { name: /Come.ar novo projecto/i }).click();
    await page.waitForURL(/#\\/conta\\/estudio/, { timeout: 15000 });
    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {
      await clickMatConfirm(page, 'Continuar');
    }
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);

`;
s = s.slice(0, i3) + new3 + s.slice(i4);

// step 2 spinner - insert after waitForSelector dashboard
const ins = "    await page.waitForSelector('app-customer-dashboard', { timeout: 15000 });\n";
if (s.includes(ins) && !s.includes("mat-spinner").includes?.("customer-dashboard")) {
  // already may have spinner in new3 only
}
const ins2 = ins + "    await page.locator('app-customer-dashboard mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});\n";
if (!s.includes("app-customer-dashboard mat-spinner")) {
  s = s.replace(ins, ins2);
}

s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');",
  "    const t1 = Date.now();\n    await page.click('button:has-text(\"Confirmar refinamento\")');\n    await clickMatConfirm(page, 'Continuar');");

s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();",
  "    const firstApprove = page.locator('.gallery-card button:has-text(\"Aprovar\")').first();\n    await firstApprove.click();\n    await clickMatConfirm(page, 'Aprovar');");

s = s.replace("    page.once('dialog', async (d) => await d.accept());\n    await page.click('button:has-text(\"Pedir arquitectura\")');",
  "    await page.click('button:has-text(\"Pedir arquitectura\")');\n    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {\n      await clickMatConfirm(page, 'Continuar');\n    }");

fs.writeFileSync(path, s, "utf8");
console.log("dialog handlers left:", (s.match(/page\.once\('dialog'/g) || []).length);
console.log("clickMatConfirm:", (s.match(/clickMatConfirm/g) || []).length);
