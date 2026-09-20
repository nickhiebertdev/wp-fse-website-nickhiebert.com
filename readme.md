# WordPress Full Site Editing (FSE) Website

Built and maintained locally in VS Code on a MacBook Pro, with changes tracked in Git and pushed to GitHub. A GitHub Actions pipeline automatically builds and deploys every push straight to the live server, no manual FTP, no manual server side steps.

## Modal CTA Notification - Dismissible Notification CTA Bar with Contact Information

A dismissible notification bar with two calls to action (Email, LinkedIn) and a close button, built as a native Gutenberg block using WordPress's Interactivity API - the same modern, framework-free approach WordPress core uses for its own interactive blocks.

Why it matters: most WordPress "custom blocks" in the wild are either static content or built with older jQuery patterns. This one uses React for the editor experience and the Interactivity API for front-end behavior, with no jQuery and no manual DOM manipulation - the dismiss state is handled declaratively.

Built with: React (JSX), PHP, WordPress Interactivity API, SCSS

Source code: [View the block source](https://github.com/nickhiebertdev/wp-fse-website-nickhiebert.com/tree/main/wp-content/plugins/wp-fse-website-nickhiebert-blocks) - edit.js, index.js, and view.js contain the React/JS logic.

![Modal CTA Notification - Website Preview](assets/Modal-CTA-Notification-Website-Preview.jpg)
![Modal CTA Notification - Header Template Part](assets/Modal-CTA-Notification-Template-Parts-In-Header.jpg)
![Modal CTA Notification - Gutenberg Block Plugin](assets/Modal-CTA-Notification-Gutenberg-Block-Plugin.jpg)
