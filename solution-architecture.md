# Architectural Approach

Diagram & narrative describing how you would refactor/re‑platform the existing WP site for

## Table of Contents

- [Multisite vs. single‑site with multilingual plugins](#multisite-vs-single‑site-with-multilingual-plugins)
- [Gutenberg-First Authoring](#gutenberg-first-authoring)
- [Headless/Decoupled Front-End with React/Next.js and SSR](#headlessdecoupled-front-end-with-reactnextjs-and-ssr)
- [CI/CD (GitHub Actions, Code Sniffer, PHPUnit)
](#cicd-github-actions-code-sniffer-phpunit)
- [Trade-Offs](#trade-offs)


## Multisite vs. single‑site with multilingual plugins

### Decision: Single-Site with Multilingual Plugins (e.g., Polylang or WPML)

- Multisite: WordPress Multisite allows multiple sites to share a single WP installation, each with its own database tables and content. It’s suitable for distinct subdomains or directories (e.g., site1.example.com, example.com/site2) with separate branding or user bases. However, managing translations across multisite requires additional plugins or custom logic, increasing complexity. Multisite also complicates database scaling, theme/plugin compatibility, and CI/CD workflows due to shared resources.

- Single-Site with Multilingual Plugins: A single-site setup with plugins like Polylang or WPML allows managing multiple languages within one WP installation. Content creators can use the familiar WP dashboard to manage translations, and plugins integrate seamlessly with Gutenberg. Polylang, for instance, supports language-specific content and exposes translations via WPGraphQL, simplifying API-driven front-ends. This approach reduces administrative overhead and is more compatible with a headless architecture.

### Implementation:

- Install and configure Polylang or WPML for
multilingual content management.

- Use WPGraphQL with the WPGraphQL Polylang extension to expose translated content via GraphQL APIs.

- Configure permalinks to include language codes (e.g., example.com/en/, example.com/es/) for SEO and user clarity.

- Store content in a single database, with Polylang managing language relationships.

### Rationale: 
Single-site with multilingual plugins is simpler to manage, more cost-effective for hosting, and aligns better with a headless setup. Multisite is better for completely separate sites but adds unnecessary complexity for language-based variations.

## Gutenberg-First Authoring

- ### Approach:
    Leverage Gutenberg’s block-based editor as the primary content creation tool, with custom blocks for flexibility.

- ### Why Gutenberg?:
    Introduced in WordPress 5.0, Gutenberg offers a modern, block-based editing experience powered by React, aligning with the headless front-end. It supports reusable blocks, custom block creation, and dynamic content, making it ideal for content editors and developers.

- ### Implementation:
    - Develop custom Gutenberg blocks using React to support specific content types (e.g., hero banners, galleries, or multilingual content sections).
    - Use @wordpress/block-library for core block styles and extend with custom CSS for consistent front-end rendering.
    - Register blocks via register_block_type in PHP, with block logic in JavaScript/React for dynamic functionality (e.g., API-driven data or interactive elements).
    - Ensure content editors can preview blocks in the WP admin dashboard, mirroring the front-end look using theme.json or custom CSS.
    - Use WPGraphQL to expose Gutenberg block data as structured JSON for the headless front-end.

- ### Benefits:
    - Familiar interface for content editors.
    - Reusable blocks reduce content duplication.
    - React-based blocks align with the Next.js front-end, improving DX.

## Headless/Decoupled Front-End with React/Next.js and SSR
- ### Approach:
    Decouple the WordPress back-end from the front-end, using WordPress as a headless CMS and Next.js for SSR.

- ### Architecture:
    - WordPress Back-End: Acts as the content management system, exposing content via WPGraphQL (preferred over REST API for precise queries and performance). Install WPGraphQL and WPGraphQL Polylang for multilingual support.
    - Next.js Front-End: A React-based framework that handles SSR, static site generation (SSG), and incremental static regeneration (ISR). Use getStaticProps for pre-rendering pages at build time and revalidate for ISR to sync with WP content updates.
    - Data Flow: Next.js queries WordPress via GraphQL to fetch content (posts, pages, translations, and block data). Render content using React components, with SSR for SEO and performance.
    - SEO: Implement meta tags, structured data, and canonical URLs in Next.js for SEO optimization. Use Yoast SEO with WPGraphQL Yoast SEO Addon for enhanced SEO data.
    - Media: Serve media via a CDN (e.g., Cloudinary) integrated with WordPress to optimize image delivery. Use Next.js Image component for automatic optimization.

- ### Implementation:
    - Set up a Next.js project with TypeScript for type safety and better DX.
    - Use graphql-request to query WPGraphQL endpoints. Example query:

        ```import { request, gql } from 'graphql-request';
        const query = gql`
        {
            posts(first: 10) {
            edges {
                node {
                id
                title
                content
                translations {
                    language
                    slug
                }
                }
            }
            }
        }
        `;
        export async function getStaticProps() {
        const data = await request('https://your-wp-site.com/graphql', query);
        return { props: { posts: data.posts }, revalidate: 60 };
        }
        ```
    - Render Gutenberg blocks as HTML using dangerouslySetInnerHTML for simple blocks or parse with html-react-parser for complex components.
    - Configure Next.js internationalization (i18n) for multilingual routing (e.g., /en, /es).
    - Deploy on Vercel for seamless scaling, CDN caching, and ISR.

- ### Benefits:
    - Improved performance via SSR and ISR.
    - Flexible, modern front-end with React/Next.js.
    - Resilient architecture: front-end remains functional if WP back-end is down, serving cached content.

## CI/CD with GitHub Actions, Code Sniffer, and PHPUnit

- ### Approach:
    Implement a CI/CD pipeline to ensure code quality, automate testing, and streamline deployments.

- ### Tools:
    - GitHub Actions: Automate build, test, and deployment workflows.
    - PHP Code Sniffer: Enforce WordPress coding standards for PHP code.
    - PHPUnit: Run unit tests for PHP-based WordPress plugins and custom code.

- ### Implementation:
    - #### Repository Structure:
        - WordPress back-end: Custom plugins/themes in a Git repository.
        - Next.js front-end: Separate repository for the React application.



    - ### GitHub Actions Workflow:
        - #### PHP Linting and Testing:
            - Run PHP Code Sniffer to check WordPress coding standards.
            - Execute PHPUnit tests for custom WP plugins (e.g., custom Gutenberg blocks).
            - Example workflow (wp-tests.yml):
                ```name: WordPress CI
                on:
                push:
                    branches: [main]
                pull_request:
                    branches: [main]
                jobs:
                test:
                    runs-on: ubuntu-latest
                    steps:
                    - uses: actions/checkout@v3
                    - name: Setup PHP
                        uses: shivammathur/setup-php@v2
                        with:
                        php-version: '8.1'
                    - name: Install Dependencies
                        run: composer install
                    - name: Run PHP Code Sniffer
                        run: vendor/bin/phpcs --standard=WordPress ./wp-content/plugins
                    - name: Run PHPUnit
                        run: vendor/bin/phpunit
                ```

        - #### Next.js Build and Deploy:
            - Build and test the Next.js app, then deploy to Vercel.
            - Example workflow (nextjs-deploy.yml):
                ```name: Next.js CI/CD
                on:
                push:
                    branches: [main]
                pull_request:
                    branches: [main]
                jobs:
                build:
                    runs-on: ubuntu-latest
                    steps:
                    - uses: actions/checkout@v3
                    - name: Setup Node.js
                        uses: actions/setup-node@v3
                        with:
                        node-version: '18'
                    - name: Install Dependencies
                        run: npm install
                    - name: Run Tests
                        run: npm test
                    - name: Build
                        run: npm run build
                    - name: Deploy to Vercel
                        env:
                        VERCEL_TOKEN: ${{ secrets.VERCEL_TOKEN }}
                        run: npx vercel --prod
                ```
        - #### Deployment:
            - WordPress: Deploy plugin/theme updates to a staging environment, then production, using WP-CLI or hosting provider tools.
            - Next.js: Deploy to Vercel, leveraging Deploy Hooks for automatic rebuilds on WP content updates.

        - ### Testing:
            - Use PHPUnit for PHP unit tests (e.g., testing custom REST API endpoints).
            - Use Jest or React Testing Library for Next.js component tests.
            - Optionally, integrate end-to-end tests with Cypress for front-end validation.

- ### Benefits:
    - Automated testing ensures code quality.
    - Faster deployments with GitHub Actions.
    - Consistent coding standards improve maintainability.



## Trade-Offs

### Cost
- ### Single-Site with Multilingual Plugins:
    - #### Pros:
        - Lower hosting costs (single WP instance). Polylang is free; WPML has a subscription. Easier to scale database and server resources.
    - #### Cons:
        - WPML’s cost for premium features. Limited to language-based variations, not ideal for fully distinct sites.

- ### Multisite:
    - #### Pros:
        - Supports distinct sites with shared codebase.
    - ##### Cons:
        - Higher hosting costs due to complex database scaling. Increased maintenance for plugins/themes across sites.

- ### Headless Setup:
    - #### Pros:
        - Vercel’s free tier or low-cost plans suffice for small sites. CDN reduces hosting costs for media.
    - #### Cons: 
        - Additional hosting for Next.js (Vercel) and WordPress. WPGraphQL plugins may require premium versions for advanced features.

- ### CI/CD:
    - #### Pros: 
        - GitHub Actions free tier is sufficient for small teams. Automated deployments reduce manual costs.

    - #### Cons: 
        - Vercel’s higher tiers ($20+/month per user) for large-scale deployments. Developer time for setting up pipelines.

### Developer Experience (DX)
- ### Single-Site with Multilingual Plugins:
    - #### Pros: 
        - Familiar WP dashboard for content editors. Polylang/WPML integrate well with Gutenberg and WPGraphQL, simplifying API queries.
    - #### Cons: 
        - Learning curve for custom Gutenberg block development in React.

- ### Multisite:
    - ### Pros: 
        - Clear separation for distinct sites.
    - ### Cons:
        - Complex plugin/theme compatibility. Harder to manage translations via API.

- ### Headless Setup:
    - #### Pros: 
        - Next.js offers modern DX with TypeScript, SSR, and ISR. React aligns with Gutenberg’s block development. Vercel simplifies deployments.
    - #### Cons: 
        - Requires expertise in React, GraphQL, and Next.js. Parsing Gutenberg blocks (e.g., via html-react-parser) can be complex.
- ### CI/CD:
    - #### Pros: 
        - GitHub Actions streamlines testing and deployment. Code Sniffer enforces standards, reducing tech debt. PHPUnit ensures robust WP code.
    - #### Cons: 
        - Initial setup time for workflows. Learning curve for non-PHP developers using PHPUnit.

### Rollout Risk
- ### Single-Site with Multilingual Plugins:
    - #### Pros: 
        - Lower risk due to simpler architecture. Polylang/WPML are mature, reducing bugs. Incremental migration possible (e.g., add languages gradually).

    - #### Cons: 
        - Misconfiguration of permalinks or translations can affect SEO.

- ### Multisite:
    - #### Pros: 
        - Isolated sites reduce risk of one site affecting others.
    - #### Cons: 
        - Higher risk of plugin conflicts or database issues. Migration from single-site to multisite is complex and error-prone.

- ### Headless Setup:
    - #### Pros: 
        - Decoupled architecture allows independent updates to front-end/back-end, reducing regression risks. ISR ensures content updates without downtime.
    - #### Cons: 
        - Initial migration requires rewriting front-end, risking delays. Rendering Gutenberg blocks as HTML may lead to styling inconsistencies.

- ### CI/CD:
    - #### Pros: 
        - Automated testing catches errors early. Staging environments (via Vercel or WP hosting) reduce production risks.
    - #### Cons: 
        - Misconfigured workflows can cause deployment failures. Dependency on Vercel’s infrastructure introduces external risk.