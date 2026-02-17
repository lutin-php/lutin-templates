# Specification: Lutin-Starters Repository System

## 1. Project Goal

Create a centralized repository system to manage, build, and distribute "Starters" (boilerplates) for the Lutin.php ecosystem. The system must package folders into `.zip` files, generate a JSON manifest, and use GitHub Releases for distribution.

## 2. Repository Structure

```text
/
├── .github/workflows/build-releases.yml  # The Automation Brain
├── scripts/
│   └── build-zips.php                    # PHP script to compress folders
├── starters/
│   ├── blog-static/                      # Example Starter
│   │   ├── public/                       # Assets/Entry point (Target: Webroot)
│   │   ├── src/                          # Private logic
│   │   ├── data/                         # Private data (SQLite, etc.)
│   │   └── lutin/
│   │       └── AGENTS.md                 # Documentation for the AI Agent
│   └── portfolio-minimal/
├── dist/                                 # Generated Zips (Git-ignored)
└── starters.json                         # Generated Manifest

```

## 3. The "Starter" Architecture (e.g., `blog-static`)

Every starter must follow the **Public/Private Split**:

* **`public/` folder:** Contains everything that must be accessible to the end-user's browser (index.php, CSS, JS, Images).
* **Private folders (`src/`, `data/`, etc.):** Contains sensitive logic and databases.
* **`lutin/AGENTS.md`:** A specialized markdown file that tells the Lutin AI:
* "The main CSS is located in `public/assets/style.css`."
* "To create a new post, add a file in `data/posts/`."



## 4. Build & Distribution System (The "Build Engine")

### 4.1 The Build Script (`scripts/build-zips.php`)

A PHP script that:

1. Iterates through each folder in `/starters/`.
2. Compresses the content into a ZIP file named `starter-name.zip`.
3. Calculates the **SHA-256 hash** of the ZIP for security verification.
4. Updates `starters.json`.

### 4.2 The Manifest (`starters.json`)

This file is the "API" that remote Lutin instances will fetch.

```json
{
  "version": "1.0",
  "starters": [
    {
      "id": "blog-static",
      "name": "Minimal Static Blog",
      "description": "A simple flat-file blog with a public/private structure.",
      "download_url": "https://github.com/USER/REPO/releases/download/v1.0.0/blog-static.zip",
      "hash": "sha256-..."
    }
  ]
}

```

### 4.3 GitHub Actions Workflow

On every push to the `main` branch:

1. Run `build-zips.php`.
2. Create a new **GitHub Release** (tagged with the date/version).
3. Upload all `.zip` files from `dist/` as **Release Assets**.
4. Update the `starters.json` in the repository with the new download URLs.

---

## 5. Installation Logic (For the Remote Lutin.php)

When a user selects a starter, the remote `lutin.php` must:

1. **Identify Local Webroot:** Detect its own directory (where `lutin.php` resides).
2. **Download & Extract:** Fetch the ZIP and extract it to a temporary folder.
3. **Smart Mapping:**
* Move the contents of the ZIP's `public/` folder **directly** into the directory where `lutin.php` is located.
* Move the other folders (`src/`, `data/`, etc.) as siblings or parents based on environment permissions.


4. **Initialize Agent:** Point the AI Agent to the newly imported `lutin/AGENTS.md` to begin the onboarding.

---

### Instructions for the AI (Kimi/Claude):

> "Based on this specification, please generate the `scripts/build-zips.php` script and a sample `.github/workflows/build-releases.yml`. Also, provide the file structure for the `blog-static` starter, including a basic `public/index.php` and a helpful `lutin/AGENTS.md`."
