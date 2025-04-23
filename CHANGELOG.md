# Changelog

## Version 4.0.4
- Fixed error 'Undefined array key picture_id' when trying to add a picture in a new gallery on an installation which prevents super global replacement
- Fixed migration-issue regarding missing title by replacing the title with basename in such cases

## Version 4.0.3
- Check in migration if possible

## Version 4.0.2
- Updated migration section in readme
- Fixed further migration issues and removed commented out parts
- Fixed some language issues
- Fixed naming-issue when downloading files which were migrated pre-migration-fix
- Fixed file-related pre- & post-migration issues and fixed naming of migrated files

## Version 4.0.1
- Fixed permission check and redirect issues

## Version 4.0.0
- Implemented ILIAS 9 support.
- Changed file storage from a deprecated older implementation to the new ILIAS Resource Storage Service (IRSS).
- Replaced deprecated UI elements with new UI-Components.
- Reintroduced navigation arrows in image previews to get to the previous or next image.
- Also added a new button to close the image preview and added an info section at the bottom.
- Reintroduced drag and drop upload as well as multi file upload.
- Made improvements to the overall code and architecture.

## [3.0.0]
* Implemented ILIAS 8 support

## [2.5.1]
* bugfix: empty multiaction leads to error with php 7.2

## [2.5.0]
* Supported Versions: ILIAS 5.4 & ILIAS 5.3
