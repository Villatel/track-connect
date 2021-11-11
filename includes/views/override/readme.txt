If you are trying to customize the track-connect plugin you can do so by overriding the files in this override folder.

Anything in this folder track-connect/views/override can be added to you child theme, just make sure its the same structure.

For example you can create a child theme and add the directory

/wp-content/themes/your-child-theme/track-connect/views/override/

Anything you put in here will override the track-connect plugin, if you don't add the override, it will default to the file in this plugin.

It does not merge the files, it simply replaces it.

There is also a helpers.php file that is always included and any custom functions needed should go there.

The way the helpers.php works is that if you define a function that is used in the, most all the functions in the plugin have a wrapper and only are defined if they don't already exist.

If you find a function that you need to customize but there isn't a wrapper around it, please submit a request and we will quickly add it.

ex. if(!function_exists('mam_posts_query')){

It is highly recommended to use a child theme, that was you can do an theme upgrade without removing these override files.