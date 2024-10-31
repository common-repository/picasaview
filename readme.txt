=== Plugin Name ===
Contributors: mindvex
Donate link: http://www.sattes-faction.de/
Tags: picasa, picasaweb, picasaview, gallery
Requires at least: 2.7
Tested up to: 3.2.1
Stable tag: trunk

picasaView enables you to view your picasaweb-albums in your blog - simply by specifing a tag in your content.

== Description ==

With picasaView you can easily view your picasaweb-albums in your blog. To achieve this, all you need to do is to insert
a shorttag like `[picasaView album='ALBUMNAME']` in your content where you want your album to appear. To view
all your public albums just use `[picasaView]`.

The plugin supports localization, paging and can be easily skinned by changing the external templates and the stylesheets
which reside within the plugin directory in the subfolders 'templates' and 'css'.
That means you could use Lightbox or any other image viewer by simply editing the html-templates to suit your needs,
for example with [Rupert Morris' excellent Lightbox2 Plugin](http://www.huddletogether.com/projects/lightbox2/) or
any other image viewer. picasaView is already configured to work with LightBox (if installed), so there is no need
for further adjustments.

Through the backend settings you can additionally adjust the size of the used thumbnails and full size images and how
much images are shown on a page at once.

More info can be found at the [picasaView Homepage](http://www.sattes-faction.de/picasaview "PicasaView Plugin").

To see what those linked galleries look like, visit the [picasaView demo page](http://www.sattes-faction.de/picasaview/picasaview-demo/).

== Installation ==

**Please keep in mind that picasaView requires PHP5 to work. To connect to picasaweb the plugin uses Wordpress' internal connection methods. Please ensure, you're server is allowed to retrieve data from external URLs. **

So this is how you install the plugin:

1. Upload the directory 'picasaview' and all of its contents to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Set up your initial connection properties through the admin settings menu.
4. Place `[picasaView]` in you content to view all your public albums. That's all. 
5. If you like to view a specific album, use `[picasaView album='ALBUMNAME']`. Instead of the album name you can also use the unique album id. The album name is the one that you see in the addressbar of your browser when entering your album on picasaweb (may be different from what you entered as album name when creating the album. For example, if it's `http://picasaweb.google.de/simon.sattes/PicasaViewDemo`
then the album name was `PicasaViewDemo`. 
If your album name contains a single quote use double quotes for the name: `[picasaView album="ALBUMNAME'S"]`.
6. new in v1.0: if you like to show the images of one album directly in a post, use the option "instantView=yes":
	e.g. `[picasaView album="ALBUMNAME'S" instantView="yes"]`

**It's possible to overwrite all settings on the admin-options page with each picasaView-tag. That gives you the possibility to view all possible albums of different users on your blog. These are the options that can be used
in the tag.**

NOTE: you can combine all options as you like and use as many of them as necessary. **If an option is missing, the general setting from the admin options page will be used instead.**

* `album`: the name of the album you'd like to view (please use the shortened part of the album name you can see in the URL on picasaWeb, e.g. "Hamburg2009" as in "http://picasaweb.google.com/USERID/Hamburg2009"
* `instantview`: if set to "yes", the album images are shown directly in the post. Can only in combination with `album`
* `authkey`: authentication key (as specified by picasaweb) for displaying private albums. Works only in combination with `album`
* `userid`: the user-Id from picasaweb (the part as in "http://picasaweb.google.com/USERID/album...")
* `server`: the URL of the picasaView server
* `thumbnailsize`: the size of the thumbnails. May be one of the following values: `32, 48, 64, 72, 144, 160, 200, 288, 320, 400, 512, 576, 640, 720, 800`
* `imagesize`: the size of the full images. May be one of the following values: `200, 288, 320, 400, 512, 576, 640, 720, 800, 1024, 1152, 1280, 1440, 1600` (note: 1024 and above will currently not work due to limitations of the picasaweb-service)
* `imagesperpage`: how many images should be shown at one page. If the album contains more images than this setting, paging is enabled
* `cropthumbnails`: if set to `yes`, the thumbnails will be cropped, if `no`, they won't (note: only works with a thumbnail size smaller than 200)
* `showdebugoutput`: shows more information on what went wrong (if an error occured).

A combination could be `[picasaView user="USERNAME" imagesPerPage='6' cropThumbnails='yes']`.

The only option that can't be set this way is `quickPaging` due to technical restrictions.

== Customization ==

To customize, create a subdirectory named 'picasaview' in your WordPress theme-directory (e.g. '/wp-content/themes/default/picasaview)' and copy the contents of the 'template' subdirectory in the picasaview plugin-dir into it.
** Don't edit the templates in the plugin directory as they will be overwritten if you auto-update to a new version **

The template files mustn't be renamed and have the following functions. With the specific placeholders explained below
you can further customize the output. Just be sure to keep the class-attributes (they are needed for the JavaScript frontend functionality)

* **album.html**: Used to display the album summary. This is the template that's shown when you insert the picasaView-tag into your content.
Supported placeholders: `%ALBUMLINK%`, `%ALBUMTITLE%`, `%THUMBNAILPATH%`, `%ALBUMSUMMARY%`, `%TOTAL_RESULTS%`, `%TOTAL_RESULTS_LABEL%`, `%CREATIONDATE%`, `%CREATIONDATE%`
* **albumDetails.html**: This is the template that's used for the image thumbnails when viewing all images of an album:
Supported placeholders: `%ALBUMID%`, `%IMAGEID%`, `%IMAGEPATH%`, `%IMAGETITLE%`, `%THUMBNAILPATH%`, `%IMAGEDESCRIPTION%`, `%INDEX%`
* **albumDetailsHeader.html**: The header for the album details view before all thumbnails are printed.
Supported placeholders: `%ALBUMTITLE%`, `%ALBUMSUMMARY%`, `%TOTAL_RESULTS%`, `%TOTAL_RESULTS_LABEL%`, `%LOCATION%`, `%LOCATION_LABEL%`, `%PREVIOUS_PAGE_LINK%`, `%PREVIOUS_PAGE_LABEL%`, `%NEXT_PAGE_LINK%`, `%NEXT_PAGE_LABEL%`, `%SHOWING_RESULTS%`, `%SHOWING_RESULTS_LABEL%`, `%TOTAL_RESULTS_LABEL%`, `%TOTAL_RESULTS%`, `%BACKTOPOST_LINK%`, `%BACKTOPOST_LABEL%`
* **albumDetailsFooter.html**: The footer for the album details view that's appended after all thumbnails are printed.
Supported placeholders: same as in albumDetailsHeader.html above

The placeholders, which mainly got their value from the data fetched from picasaweb, have the following meanings:

* **`%ALBUMLINK%`**: The link to the album details page. This placeholder is used in album.html only
* **`%ALBUMTITLE%`**: The album title
* **`%ALBUMSUMMARY%`**: The album summary (plain text)
* **`%ALBUMID%`**: The unique album id (used for Lightbox-"rel"-attributes e.g. as in albumDetails.html
* **`%CREATIONDATE%`**: the date, the album was published for the first time
* **`%MODIFICATIONDATE%`**: the date, the album was modified the last time
* **`%THUMBNAILPATH%`**: The absolute path of the thumbnail
* **`%IMAGEID%`**: Unique image-id (taken from picasaweb)
* **`%IMAGEPATH%`**: The absolute path of the full-size image
* **`%IMAGETITLE%`**: The image title (like 'image-023.jpg')
* **`%IMAGEDESCRIPTION%`**: The image description
* **`%IMAGEPATH%`**: The absolute path of the full-size image
* **`%LOCATION%`**: The location where the photos were taken
* **`%LOCATION_LABEL%`**: the localized string "Location"
* **`%TOTAL_RESULTS%`**: The total number of photos in the current album
* **`%TOTAL_RESULTS_LABEL%`**: the localized string "Photos"
* **`%PREVIOUS_PAGE_LABEL%`**: the localized string "Previous (Page)"
* **`%NEXT_PAGE_LABEL%`**: the localized string "Next (Page)"
* **`%SHOWING_RESULTS_LABEL%`**: the localized string "Viewing images"
* **`%TOTAL_RESULTS_LABEL%`**: the localized string "of" (in the string "viewing images 1-10 of 90" e.g.)
* **`%NEXT_PAGE_LINK%`**: the href-link to the next page of the album
* **`%PREVIOUS_PAGE_LINK%`**: the href-link to the previous page of the album
* **`%BACKTOPOST_LINK%`**: the permalink of the page where the picasaView has been called
* **`%BACKTOPOST_LABEL%`**:  the localized string "Back to post"
* **`%INDEX%`**:  the index number of each image starting with 1 and counting up

Furthermore there are some IF-Blocks which can be used in the templates *albumDetailsHeader.html and *albumDetailsFooter.html*
to let picasaView hide blocks which make no sense in the current context (for example, a "previous"-link would not make sense
on the first page). If such a block becomes unnecessary it will be removed. The following examples are named like the placeholders
above. Currently, the following statements are supported:

* **`%IF_LOCATION%` and `%ENDIF_LOCATION%`**
* **`%IF_PREVIOUS_PAGE%` and `%ENDIF_PREVIOUS_PAGE%`**
* **`%IF_NEXT_PAGE%` and `%ENDIF_NEXT_PAGE%`**
* **`%IF_BACKTOPOST%` and `%ENDIF_BACKTOPOST%`**

*Please do not rename the used CSS-classes if you're using the quick paging option as it will break the functionality.*

== Frequently Asked Questions ==

= The plugin fails with the message "Could not load data from picasaweb. Please check your connection settings." =

That means either your connection settings are wrong (i.e. wrong picasa-url or wrong picasa user-id) or
the plugin can't connect to picasaweb. The failure message from picasaweb should appear below. If you have problems
troubleshooting, don't hesitate to contact me (along with as many infos as possible, like your config settings, WordPress-
and PHP-Version and so on).

= The plugin fails with the message "picasaView plugin: template file '$template.html' not found or not readable." =

This means that neither a custom template nor the default templates were found or not readable. If you use custom templates
please ensure they are placed in 'WP_DIR/wp_content/picasaview_templates' and have the same name like in
the default templates dir in the picasaView-plugin directory.

= I'm sure I entered the correct User-Id and URL but I get a message saying no album exist for this user =

If you're really sure your data is correct, ensure that the albums of your picasaweb-user are made public or you specified an authkey
for private albums.

= I've specified an album in the tag but get an error message saying this album was not available =
Be sure to use the shorted album name picasaweb generates, not the original one. For example the album "Cats And Dogs" is shrinked to "CatsAndDogs".
You can see the shortened album name if you click on an album in picasaweb and look at the url: "http://picasaweb.google.com/username/CatsAndDogs".

== Changelog ==

For a complete history please [look here](http://www.sattes-faction.de/picasaview/history/).

Changes in this version:
* FIX: A stupid JavaScript error caused some other backend-functionality to fail

 == To Do ==

 * pagination on album overview
 * add link to the original album on picasaweb
 * anchor link to the detail view of an album
 * sidebar widget with random images
 * directly insert single images in your posts
 * add caching of images
 * commenting of photos
 * support for speaking URLs