import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { fixupPluginRules } from '@eslint/compat';
import { FlatCompat } from '@eslint/eslintrc';
import js from '@eslint/js';
import tseslint from '@eslint/js';
import typescriptEslint from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import * as astroParser from 'astro-eslint-parser';
import compat from 'eslint-plugin-compat';
import _import from 'eslint-plugin-import';
import eslintPluginAstro from 'eslint-plugin-astro';
import perfectionist from 'eslint-plugin-perfectionist';
import prettier from 'eslint-plugin-prettier';
import pluginPromise from 'eslint-plugin-promise';
import globals from 'globals';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const flatCompat = new FlatCompat({
    baseDirectory: __dirname,
    recommendedConfig: js.configs.recommended,
    allConfig: js.configs.all,
});

const files = ['**/*.ts'];

// Rules shared between .ts and the <script>/frontmatter of .astro files
const sharedRules = {
    ...js.configs.recommended.rules,
    ...tseslint.configs.strict,
    '@typescript-eslint/explicit-function-return-type': 'warn',
    '@typescript-eslint/no-unused-vars': 'error',
    'accessor-pairs': 'error',
    'block-scoped-var': 'error',
    'camelcase': 'error',
    'dot-notation': 'warn',
    'eqeqeq': ['error', 'always'],
    'import/no-unresolved': ['error', { ignore: ['^astro:'] }],
    'import/order': [
        'error',
        {
            groups: [
                'builtin',
                'external',
                'internal',
                ['sibling', 'parent'],
                'index',
                'unknown',
            ],
            'newlines-between': 'always',
            alphabetize: {
                order: 'asc',
                caseInsensitive: true,
            },
        },
    ],
    'no-console': 'error',
    'no-eq-null': 'error',
    'no-extra-bind': 'error',
    'no-implicit-coercion': 'error',
    'no-implicit-globals': 'error',
    'no-invalid-this': 'error',
    'no-return-assign': 'error',
    'no-sequences': 'error',
    'no-template-curly-in-string': 'error',
    'no-throw-literal': 'error',
    'no-unused-vars': 'off',
    'no-use-before-define': 'error',
    'no-var': 'error',
    'perfectionist/sort-exports': 'error',
    'perfectionist/sort-named-exports': 'error',
    'prefer-arrow-callback': 'error',
    'prefer-const': 'error',
    'promise/always-return': 'error',
    'promise/avoid-new': 'off',
    'promise/catch-or-return': 'error',
    'promise/no-callback-in-promise': 'warn',
    'promise/no-multiple-resolved': 'error',
    'promise/no-native': 'off',
    'promise/no-nesting': 'warn',
    'promise/no-new-statics': 'error',
    'promise/no-promise-in-callback': 'warn',
    'promise/no-return-in-finally': 'warn',
    'promise/no-return-wrap': 'error',
    'promise/param-names': 'error',
    'promise/valid-params': 'warn',
    'require-atomic-updates': 'warn',
    'require-await': 'error',
    'import/extensions': ['error', 'ignorePackages', { ts: 'always' }],
    'sort-imports': [
        'error',
        {
            ignoreCase: false,
            ignoreDeclarationSort: true,
            ignoreMemberSort: false,
            memberSyntaxSortOrder: ['none', 'all', 'multiple', 'single'],
            allowSeparatedGroups: true,
        },
    ],
};

const sharedPlugins = {
    '@typescript-eslint': typescriptEslint,
    prettier,
    import: fixupPluginRules(_import),
    perfectionist,
};

export default [
    pluginPromise.configs['flat/recommended'],
    compat.configs['flat/recommended'],

    // Astro-specific recommended rules + sets up the astro parser for .astro files
    ...eslintPluginAstro.configs['flat/recommended'],

    ...flatCompat
        .extends(
            'eslint:recommended',
            'plugin:@typescript-eslint/recommended',
            'plugin:prettier/recommended',
        )
        .map((config) => ({
            ...config,
            files: files,
        })),

    // Plain .ts files
    {
        files: files,
        plugins: sharedPlugins,
        languageOptions: {
            globals: {
                ...Object.fromEntries(
                    Object.entries(globals.browser).map(([key]) => [key, 'off']),
                ),
                ...globals.node,
                ...globals.browser,
            },
            parser: tsParser,
            ecmaVersion: 2020,
            sourceType: 'module',
            parserOptions: {
                project: 'tsconfig.json',
            },
        },
        settings: {
            'import/resolver': {
                typescript: {
                    project: './tsconfig.json',
                },
            },
        },
        rules: sharedRules,
    },

    // TypeScript inside .astro files (frontmatter + <script>)
    {
        files: ['**/*.astro'],
        plugins: sharedPlugins,
        languageOptions: {
            globals: {
                ...globals.node,
                ...globals.browser,
            },
            parser: astroParser,
            ecmaVersion: 2020,
            sourceType: 'module',
            parserOptions: {
                // parse the embedded scripts / frontmatter as TS
                parser: tsParser,
                extraFileExtensions: ['.astro'],
                project: 'tsconfig.json',
            },
        },
        settings: {
            'import/resolver': {
                typescript: {
                    project: './tsconfig.json',
                },
            },
        },
        rules: {
            ...sharedRules,
            // .astro component imports carry an extension
            'import/extensions': [
                'error',
                'ignorePackages',
                { ts: 'always', astro: 'always' },
            ],
        },
    },
];