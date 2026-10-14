// Checking every sidebar link on every page is O(n^2) and slow in a real
// browser, so instead: one representative page per nav group, checked
// against every *other* group's representative (small, fixed fan-out), plus
// the actual first/last page of each prev/next category to confirm the
// boundary has no dangling Previous/Next card.
//
// Note: "page" is the actual prev/next category name (see src/lib/pageNav.ts)
// — it covers the homepage, Getting started, Guide and Reference sections
// chained together, not just what the sidebar labels "Guide".
import { type Page, expect, test } from "@playwright/test";

interface Representative {
  url: string;
  title: string;
}

const REPRESENTATIVES: Record<string, Representative> = {
  index: { url: "/", title: "Ember Nexus: Knowledge Graph API" },
  guide: { url: "/guide/search", title: "Search" },
  endpoint: { url: "/api/get-parents", title: "Get parents endpoint" },
  command: {
    url: "/command/cache-clear-etag",
    title: "cache:clear:etag command",
  },
};

interface CategoryBoundary {
  first: Representative;
  last: Representative;
}

// The "page" category's boundary is the homepage and the last Reference
// page, not a Guide page — see the note above.
const BOUNDARIES: Record<string, CategoryBoundary> = {
  page: {
    first: { url: "/", title: "Ember Nexus: Knowledge Graph API" },
    last: { url: "/reference/schemas", title: "Schemas" },
  },
  endpoint: {
    first: { url: "/api/get-index", title: "Get index endpoint" },
    last: {
      url: "/api/get-error-501-not-implemented",
      title: "501 not implemented endpoint",
    },
  },
  command: {
    first: { url: "/command/backup-create", title: "backup:create command" },
    last: { url: "/command/user-create", title: "user:create command" },
  },
};

async function expectOnPage(page: Page, target: Representative): Promise<void> {
  await expect(page).toHaveURL(
    new RegExp(`${target.url.replace(/\/$/, "")}/?$`),
  );
  await expect(page).toHaveTitle(target.title);
}

test.describe("cross-group sidebar navigation", () => {
  for (const [fromGroup, from] of Object.entries(REPRESENTATIVES)) {
    for (const [toGroup, to] of Object.entries(REPRESENTATIVES)) {
      if (fromGroup === toGroup) continue;

      test(`${fromGroup} -> ${toGroup} via sidebar`, async ({ page }) => {
        await page.goto(from.url);
        const link = page.locator(`#sidebar a[href="${to.url}"]`).first();
        await expect(
          link,
          `sidebar on ${from.url} links to ${to.url}`,
        ).toBeVisible();
        await link.click();
        await expectOnPage(page, to);
      });
    }
  }
});

test.describe("prev/next navigation", () => {
  for (const [group, { url }] of Object.entries(REPRESENTATIVES)) {
    test(`${group} representative page's Next/Previous cards navigate correctly`, async ({
      page,
    }) => {
      await page.goto(url);
      const nav = page.locator(".pn-nav");
      await expect(nav, `${url} has a prev/next block`).toBeVisible();

      const next = nav.locator(".pn-next");
      if (await next.count()) {
        const targetHref = await next.getAttribute("href");
        await next.click();
        await expect(page).toHaveURL(
          new RegExp(`${targetHref!.replace(/\/$/, "")}/?$`),
        );
        await page.goBack();
      }

      const prev = nav.locator(".pn-prev");
      if (await prev.count()) {
        const targetHref = await prev.getAttribute("href");
        await prev.click();
        await expect(page).toHaveURL(
          new RegExp(`${targetHref!.replace(/\/$/, "")}/?$`),
        );
      }
    });
  }
});

test.describe("prev/next boundaries — no dangling card at the start/end of a category", () => {
  for (const [category, { first, last }] of Object.entries(BOUNDARIES)) {
    test(`${category}: first page (${first.url}) has no Previous card`, async ({
      page,
    }) => {
      await page.goto(first.url);
      await expect(page.locator(".pn-prev")).toHaveCount(0);
      await expect(page.locator(".pn-next")).toHaveCount(1);
    });

    test(`${category}: last page (${last.url}) has no Next card`, async ({
      page,
    }) => {
      await page.goto(last.url);
      await expect(page.locator(".pn-next")).toHaveCount(0);
      await expect(page.locator(".pn-prev")).toHaveCount(1);
    });
  }
});
