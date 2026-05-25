const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
s = s.replace(/Come\.ar novo projecto/i, "Começar novo projecto");
if (!s.includes("const timings = {}")) {
  s = s.replace("const results = [];", "const results = [];\nconst timings = {};");
  s = s.replace(
    "function log(step, status, detail = '') {",
    "function log(step, status, detail = '', ms) {\n  if (ms != null) timings[step] = ms;"
  );
}
fs.writeFileSync(p, s, "utf8");
