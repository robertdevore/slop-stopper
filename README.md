# Slop Stopper

## Overview

**Slop Stopper** is a WordPress® plugin that scans submitted content for AI-generated phrases and flags questionable posts for administrator review. 

This makes sure that user-generated content meets quality standards and prevents generic or low-effort AI-generated text from being published without oversight.

## Features

- Automatically scans post content for flagged AI-generated phrases.
- Prevents non-admin users from publishing flagged content.
- Adds flagged posts to an admin review queue.
- Provides an admin settings page to review flagged posts.
- Includes a manual flagging option via a post meta box.

## Installation

### Requirements

- WordPress® 5.8 or higher
- PHP 7.4 or higher

### Install from GitHub

1. **Download the Plugin:**
    - Go to the Slop Stopper [GitHub Repository](https://github.com/robertdevore/slop-stopper/) (you are here)
    - Click on the **Code** button and download the ZIP file.

2. **Upload to WordPress®:**
    - Log in to your WordPress® admin panel.
    - Navigate to **Plugins > Add New**.
    - Click on **Upload Plugin** and select the ZIP file.
    - Click **Install Now**, then **Activate** the plugin.

## Usage

### How It Works

- When a non-admin user submits content, the plugin scans the post for flagged phrases.
- If a flagged phrase is detected, the post is saved as a **draft** and cannot be published until reviewed.
- An admin notice is displayed if a post contains flagged content.
- Admin users can review flagged posts in the **Slop Stopper** settings page.

### Reviewing Flagged Content

1. Navigate to **Slop Stopper > Flagged Posts** in the WordPress® admin menu.
2. Review the list of flagged posts, including details such as:
    - Post Title
    - Author
    - Date
    - Flagged Phrase
3. Edit and approve or reject flagged posts as needed.

### Manually Flagging Posts

- Editors and administrators can manually flag AI-generated content by checking the **SLOP** meta box when editing a post.
- This prevents the post from being published until further review.

## Configuration

### Customizing Flagged Phrases

To add or remove phrases from the flagged list, use the following filter in your theme's `functions.php` file or a custom plugin:
    ```php
    add_filter( 'slop_stopper_phrases', function( $phrases ) {
        $phrases[] = 'next-gen synergy'; // Add a custom phrase
        return $phrases;
    } );
    ```

## Updating the Plugin

Since this plugin is not in the WordPress® repository, it uses GitHub updates.

- The plugin will check for updates from GitHub.
- When an update is available, you will see a notification in **Plugins > Installed Plugins**.
- Click **Update Now** to install the latest version.

## Contributing

Contributions are welcome! If you'd like to improve this plugin:

1. Fork the repository on GitHub.
2. Create a new branch for your feature or bug fix (ex: `feature/feature-name`).
3. Submit a pull request.

## License

Slop Stopper is licensed under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).