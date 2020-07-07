# Klicktipp Sync for Joomla
This Joomla plugin creates/updates/deletes users in a specified Klicktipp account and assigns them a corresponding tag.

## Setup
**You'll need a Klicktipp Enterprise account for the REST API to work!**

* Download the PHP connector (*phpconnector.zip*) from [https://www.klick-tipp.com/handbuch/php-wrapper](https://www.klick-tipp.com/handbuch/php-wrapper)
* Unzip the contents of the Klicktipp archive and move the *klicktipp.api.inc* into the root folder of the downloaded klicktipp_sync plugin .zip.
* [Install the "new" plugin .zip in Joomla](https://docs.joomla.org/Installing_an_extension)

## Plugin settings (parameters)
Go to the [settings page](https://docs.joomla.org/Administration_of_a_Plugin_in_Joomla) of the newly installed plugin in Joomla.  
You can search for "Klicktipp Sync".

Enter your **Klicktipp username and password**.
Don't forget to **create a tag** for all website users in your Klicktipp dashboard and make sure that you fill that in too.

Save the changes. After that, all created or updated users will be exported to Klicktipp.  
When deleting a user with an existing Klicktipp copy, the Klicktipp user will deleted.

## Logs
You can find the logs at `/logs/klicktipp_sync.log.php`. Access to this file from a browser is disallowed.
