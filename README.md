# PHP Wrapper for Gmail API

## Description

GmailPHP is a PHP wrapper that makes it easier to use the messaging API provided by Google. This library makes use of [PHPMailer](https://github.com/PHPMailer/PHPMailer) and [Google's PHP SDK V1](https://github.com/google/google-api-php-client/tree/v1-master).

Usage notes:

In addition to the source code, example files with implementation of every function of this wrapper are added in the examples directory. Sample config file is also added, which content should be replaced with the real APP name, client details and developer key.

Available functions:

* Login (examples/login.php)
* Send message (examples/send.php)
* List messages (examples/messages.php)
* View message (examples/message_details.php)
* Delete message (examples/delete.php)
* Add label (examples/add_remove_labels.php)
* List labels (examples/labels.php)
* Create label (examples/create_label.php)

Additional functions that can be used similarly to the ones in the examples are removing a label, undoing a delete operation and creating a draft message.

## License

GmailPHP is released under the GPL v3 (or later) license, see misc/gpl-3.0.txt

## Support

Please direct any feedback to trajchevska@adevait.com
