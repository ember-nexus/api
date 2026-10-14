import { defineEcConfig } from 'astro-expressive-code'
import { pluginCollapsibleSections } from '@expressive-code/plugin-collapsible-sections'
import { schemaLinks, translateNo } from './src/plugins/expressiveCode/index.ts'

export default defineEcConfig({
    themes: ['min-dark', 'min-light'],
    useDarkModeMediaQuery: true,
    defaultProps: {
        frame: 'none',
        collapseStyle: 'collapsible-start',
    },
    plugins: [pluginCollapsibleSections(), schemaLinks(), translateNo()],
    styleOverrides: {
        collapsibleSections: {
            closedBackgroundColor: '#e4e4e7',
            openBackgroundColorCollapsible: '#f4f4f5'
        },
    },
})
