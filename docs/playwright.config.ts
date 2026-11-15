import { defineConfig, devices } from "@playwright/test";

// Runs against a preview server of the *built* site (dist/), started by
// tools/run-e2e.sh before this config's test runner is invoked — not the
// live-reloading dev server. See task check:e2e in taskfile.yml.
const baseURL = process.env.BASE_URL ?? "http://127.0.0.1:4322";

// The tests must not depend on external services (analytics, CDNs, ...): a hanging third-party
// request keeps `networkidle` from ever firing. Chromium therefore fails DNS resolution for every
// host except the one serving the site, so external requests error out immediately.
const allowedHosts = [new URL(baseURL).hostname, "127.0.0.1", "localhost"];
const hostResolverRules = `MAP * ~NOTFOUND, ${allowedHosts.map((host) => `EXCLUDE ${host}`).join(", ")}`;

export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: [["list"]],
  use: {
    baseURL,
    trace: "retain-on-failure",
  },
  projects: [
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
        launchOptions: { args: [`--host-resolver-rules=${hostResolverRules}`] },
      },
    },
  ],
});
