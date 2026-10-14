// Loads every page the build actually produced (from dist/sitemap-0.xml, so
// this can't drift from the real route list) once each, and asserts it
// didn't log a console error or throw an uncaught exception. This is the
// check that catches broken client-side JS (Alpine directives, the G6 graph
// viewer, ...) that no vitest-level test can see.
//
// It also runs an axe-core accessibility scan per page, but — per team
// decision — that's informative only for now: violations are logged and
// attached to the report, never fail the test.
import { readFileSync } from "node:fs";
import { join } from "node:path";

import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

const SITEMAP_PATH = join(import.meta.dirname, "../../dist/sitemap-0.xml");

function loadRoutes(): string[] {
  const xml = readFileSync(SITEMAP_PATH, "utf-8");
  const locs = [...xml.matchAll(/<loc>(.*?)<\/loc>/g)].map((m) => m[1]);
  return locs.map((loc) => new URL(loc).pathname);
}

const routes = loadRoutes();

test.describe.configure({ mode: "parallel" });

test("at least one route was found in the sitemap", () => {
  expect(routes.length).toBeGreaterThan(0);
});

for (const route of routes) {
  test(`page health — ${route}`, async ({ page }, testInfo) => {
    const consoleErrors: string[] = [];
    page.on("console", (msg) => {
      if (msg.type() === "error") consoleErrors.push(msg.text());
    });

    const pageErrors: string[] = [];
    page.on("pageerror", (err) => pageErrors.push(err.message));

    const response = await page.goto(route, { waitUntil: "networkidle" });
    expect(response?.status(), `HTTP status for ${route}`).toBeLessThan(400);

    // Informative-only accessibility scan — never fails this test.
    const axeResults = await new AxeBuilder({ page }).analyze();
    if (axeResults.violations.length > 0) {
      await testInfo.attach("axe-violations.json", {
        body: JSON.stringify(axeResults.violations, null, 2),
        contentType: "application/json",
      });
      console.log(
        `[axe] ${route}: ${axeResults.violations.length} violation type(s), ` +
          axeResults.violations.map((v) => `${v.id} (${v.impact})`).join(", "),
      );
    }

    expect(consoleErrors, "console.error messages").toEqual([]);
    expect(pageErrors, "uncaught exceptions").toEqual([]);
  });
}
