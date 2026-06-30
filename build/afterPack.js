const { execFileSync } = require("node:child_process");

exports.default = async function afterPack(context) {
  if (context.electronPlatformName !== "darwin") {
    return;
  }

  execFileSync("xattr", ["-cr", context.appOutDir], { stdio: "inherit" });
};
