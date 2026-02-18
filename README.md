# Lutin-Templates

[![Build Releases](https://github.com/USER/REPO/actions/workflows/build-releases.yml/badge.svg)](https://github.com/USER/REPO/actions/workflows/build-releases.yml)

Centralized repository for managing, building, and distributing project templates for the [Lutin.php](https://github.com/lutin-php/lutin-php) ecosystem.

## Overview

Lutin-Templates packages boilerplate projects into distributable ZIP files, generates a JSON manifest, and uses GitHub Releases for distribution. Remote Lutin.php instances fetch the manifest to offer one-click template installation to users.

## Available Templates

| Template | Description | Size |
|---------|-------------|------|
| [blog-static](./templates/blog-static) | Minimal flat-file blog with public/private structure | ~5 KB |

## Repository Structure

```
├── .github/workflows/build-releases.yml  # CI/CD automation
├── scripts/build-zips.php                # Build script
├── templates/                            # Project templates
│   └── blog-static/                      # Example: blog template
│       ├── public/                       # Web root files
│       ├── data/                         # Private data
│       └── lutin/AGENTS.md               # AI guidance
├── dist/                                 # Generated ZIPs (git-ignored)
└── templates.json                        # Generated manifest
```

## Quick Start

### For Users (Installing Templates)

When you first access Lutin.php, the setup wizard will guide you through selecting and installing a project template:

1. Complete the initial setup (password, API provider, data directory)
2. **Select a template** from the available options
3. Lutin downloads and extracts the template automatically
4. Start building your project with AI assistance

The template installation is a mandatory step during first-time setup, ensuring every project begins with a solid foundation.

### For Contributors (Adding Templates)

1. **Fork this repository**

2. **Create your template** in `templates/your-template-name/`:
   ```
   templates/your-template-name/
   ├── public/              # Required: Web-accessible files
   │   └── index.php        # Required: Entry point
   ├── lutin/
   │   └── AGENTS.md        # Required: AI documentation
   └── [other directories]  # Optional: Private code/data
   ```

3. **Test locally**:
   ```bash
   php scripts/build-zips.php
   ```

4. **Submit a Pull Request**

## Template Architecture

### The Public/Private Split

All templates must follow this security-conscious structure:

| Location | Purpose | Installation Target |
|----------|---------|---------------------|
| `public/` | Web-accessible files (HTML, CSS, JS, images) | Web root (with `lutin.php`) |
| `src/` | Private PHP logic | Outside web root |
| `data/` | Databases, user content | Outside web root |
| `lutin/` | AI agent documentation | Outside web root |

This ensures sensitive files are never directly accessible via HTTP.

### The `AGENTS.md` File

Each template must include `lutin/AGENTS.md` — this tells the Lutin AI:

- Where key files are located
- How to add content (posts, pages, etc.)
- How to customize the design
- Architecture and security notes

See [blog-static/lutin/AGENTS.md](./templates/blog-static/lutin/AGENTS.md) for an example.

## Build System

### Local Build

Requirements: PHP 8.1+ with `zip` extension

```bash
# Build all templates and generate manifest
php scripts/build-zips.php

# Output:
# - dist/*.zip (template packages)
# - templates.json (manifest with hashes)
```

### Running Tests

```bash
# Run all tests
php scripts/run-tests.php
```

Tests verify:
- Build script creates valid ZIP archives
- `templates.json` has correct structure and hashes
- ZIP files contain expected files (public/, index.php, etc.)
- Environment variable validation

### Automated Releases

On every push to `main`:

1. GitHub Actions runs the build script
2. Creates a new Release with generated ZIPs as assets
3. Updates `templates.json` with download URLs
4. Commits the updated manifest

## The Manifest (`templates.json`)

The manifest is the public API that remote Lutin instances consume:

```json
{
  "version": "2026.02.16-abc123",
  "generated_at": "2026-02-16T12:00:00+00:00",
  "templates": [
    {
      "id": "blog-static",
      "name": "Minimal Static Blog",
      "description": "A simple flat-file blog with a public/private structure.",
      "hash": "sha256-abc123...",
      "size": 5123,
      "zip_name": "blog-static.zip",
      "download_url": "https://github.com/USER/REPO/releases/download/v1.0.0/blog-static.zip"
    }
  ]
}
```

Fetch the raw manifest:
```
https://raw.githubusercontent.com/USER/REPO/main/templates.json
```

## Template Ideas

- [x] **blog-static** — Flat-file blog
- [ ] **portfolio-minimal** — Clean portfolio site
- [ ] **docs-site** — Documentation template
- [ ] **landing-page** — Marketing landing page
- [ ] **admin-dashboard** — Simple admin panel
- [ ] **api-template** — REST API boilerplate

Want to contribute a template? See [Contributing](#contributing).

## License

MIT License — see [LICENSE](./LICENSE) for details.

## Related Projects

- [Lutin.php](https://github.com/lutin-php/lutin-php) — The AI-powered PHP development environment
