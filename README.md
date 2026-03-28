# Elementor YouTube Feed & Importer (v2.0)

A powerful WordPress tool that automates the process of fetching YouTube videos from a specific channel and displaying them using a high-end Elementor widget. It features a unique auto-categorization system based on video titles and a professional "Hero + Carousel" presentation.

## Key Features

* **Automated Video Importer**: Connect via YouTube Data API v3 to fetch the latest videos from any Channel ID with a single click.
* **Smart Auto-Categorization**: Automatically sorts videos into WordPress categories by parsing titles. If a video title contains a pipe (e.g., "My Video | Tutorials"), the plugin creates and assigns it to the "Tutorials" category.
* **Custom Post Type Integration**: Imported videos are stored as `yt_video` posts, allowing them to be managed, searched, and archived like standard WordPress content.
* **Premium Elementor Widget**:
    * **Hero Layout**: Automatically features the most recent video in a large, high-impact card.
    * **Swiper.js Carousel**: Displays remaining videos in a smooth, touch-responsive slider.
    * **Fully Configurable**: Set the total number of videos to display directly within the Elementor editor.
* **Optimized Performance**: Uses `media_sideload_image` to download YouTube thumbnails to your local server, ensuring faster load times and better SEO.
* **Responsive Design**: Mobile-ready CSS that transitions from a multi-column grid to a single-card stack on smaller devices.

## Installation

1.  Upload the `youtube-bootstrap` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > YT Feed Settings** to enter your Google API Key and YouTube Channel ID.
4.  Click **Fetch Latest Videos** to perform your first sync.

## How to Display

1.  Open any page in **Elementor**.
2.  Search for the **YT Channel Feed** widget.
3.  Drag it onto your page and customize the "Total Videos" count in the Content tab.

## Technical Details

* **Custom Post Type**: `yt_video`
* **Taxonomy**: `video_category`
* **Dependencies**: Requires Elementor and utilizes the built-in Swiper.js library.
* **API Logic**: Uses `wp_remote_get` for secure communication with Google servers.

## Author
**D Kandekore**
