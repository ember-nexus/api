import { defineConfig, devices } from "@playwright/test";

// Runs against a preview server of the *built* site (dist/), started by
// tools/run-e2e.sh before this config's test runner is invoked — not the
// live-reloading dev server. See task check:e2e in taskfile.yml.
export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: [["list"]],
  use: {
    baseURL: process.env.BASE_URL ?? "http://localhost:4322",
    trace: "retain-on-failure",
  },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
});
