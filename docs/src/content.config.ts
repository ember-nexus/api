import { glob } from "astro/loaders";
import { defineCollection, z } from "astro:content";

const pages = defineCollection({
  loader: glob({ base: "./src/data/pages", pattern: "**/*.{md,mdx}" }),
  schema: z.object({
    type: z.string(),
    title: z.string(),
    name: z.string().optional(),
    description: z.string().optional(),
    hidden: z.boolean().optional().default(false),
  }),
});

const endpoints = defineCollection({
  loader: glob({ base: "./src/data/endpoints", pattern: "**/*.{md,mdx}" }),
  schema: z.object({
    endpoint: z.string(),
    group: z.string(),
    method: z.string(),
    endpointUrl: z.string(),
    swaggerUrl: z.string().optional(),
    name: z.string(),
    description: z.string().optional(),
  }),
});

const commands = defineCollection({
  loader: glob({ base: "./src/data/commands", pattern: "**/*.{md,mdx}" }),
  schema: z.object({
    command: z.string(),
    name: z.string().optional(),
    description: z.string().optional(),
    helpCommand: z.string(),
    helpOutput: z.string(),
    exampleCommand: z.string(),
    exampleOutput: z.string(),
  }),
});

export const collections = { pages, endpoints, commands };
