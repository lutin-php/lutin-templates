# Project Guide

This is your flat-file blog. All content is stored as HTML files — no database required.

## Project Structure

```
├── public/              # Web-accessible files
│   ├── index.php        # Blog homepage
│   ├── post.php         # Single post view
│   ├── about.php        # About page
│   └── assets/
│       └── style.css    # Main stylesheet
├── src/                 # Private PHP logic
├── data/
│   └── posts/           # HTML blog posts
└── lutin/
    └── AGENTS.md        # This file
```

## Managing Content

### Adding a New Blog Post

1. Create a file in `data/posts/` named with the pattern:
   ```
   YYYY-MM-DD-your-post-slug.html
   ```

2. Write your content using HTML:
   ```html
   <h1>Your Post Title</h1>
   
   <p>Your content here...</p>
   
   <p>Multiple paragraphs are supported.</p>
   ```

3. The post appears automatically on the homepage

### Editing Existing Posts

Simply modify the HTML files in `data/posts/`. Changes are immediate.

### HTML Tips

- The first `<h1>` tag is used as the post title
- The first `<p>` tag is used as the excerpt on the homepage
- All HTML in the file is included directly in the page
- Use standard HTML tags for formatting

## Customization

### Site Title
Edit the `<h1>` text in:
- `public/index.php` (homepage header)
- `public/post.php` (post page header)
- `public/about.php` (about page header)

### Colors & Styling
Edit `public/assets/style.css`:

```css
:root {
    --color-primary: #3b82f6;    /* Links, buttons */
    --color-text: #1f2937;       /* Body text */
    --color-bg: #ffffff;         /* Background */
    --color-bg-alt: #f9fafb;     /* Alternate background */
}
```

### Adding Pages
1. Create `public/your-page.php`
2. Copy structure from `public/about.php`
3. Add navigation link in all page headers

## Security Notes

- `data/` is outside the web root and cannot be accessed directly
- Post URLs are validated to prevent directory traversal
- Always sanitize any new user input if extending functionality
