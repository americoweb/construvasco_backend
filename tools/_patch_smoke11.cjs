const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
s = s.replace(/projectStep\.locator\(/g, "projectStep.locator(").replace(/briefingStep\.locator\(/g, "briefingStep.locator(");
s = s.replace(/projectStep\.locator\('([^']+)'\)/g, "projectStep.locator('$1').first()");
s = s.replace(/briefingStep\.locator\('([^']+)'\)/g, "briefingStep.locator('$1').first()");
fs.writeFileSync(p, s, "utf8");
