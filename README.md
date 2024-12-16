# WD Dynamic Variable Replacer

**Version:** 1.5  
**Author:** Wowk Digital

## Overview

WD Dynamic Variable Replacer is a WordPress plugin that allows you to dynamically replace placeholders in your post or page content with custom values defined by you. It integrates seamlessly with the Divi editor, highlighting variables directly in the editing interface. Additionally, the plugin supports importing and exporting your key-value configuration in JSON format.

## Features

- **Dynamic Variable Replacement:**  
  Replace any placeholder `$$key$$` in your content with a corresponding value defined in the plugin’s settings.
  
- **Divi Integration:**  
  When editing content with the Divi builder, variables are visually highlighted so you can easily identify placeholders.
  
- **JSON Import/Export:**  
  Easily back up, migrate, or share your configuration by exporting it to a JSON file. You can also import a JSON file to override your current key-value pairs.

## Installation

1. Download the plugin files or install directly through the WordPress plugin directory (if available).
2. Navigate to **Plugins** > **Add New** in your WordPress dashboard.
3. Click **Upload Plugin**, select the `.zip` file, and click **Install Now**.
4. Once installed, click **Activate**.

## Usage Instructions

1. **Define Variables:**
   - Go to **WD Dynamic Variable Replacer** in your WordPress admin sidebar.
   - Add key-value pairs under “Variable Settings.”
   - Save changes.

2. **Use Variables in Content:**
   - In your pages or posts, insert `$$key$$` where `key` matches the key you defined.
   - The plugin will replace every occurrence of `$$key$$` with the specified value.

3. **Importing/Exporting Settings:**
   - To export, click **Export to JSON** to download a `.json` file containing your current configuration.
   - To import, upload a `.json` file through the **Import from JSON File** section to overwrite your current key-value pairs.

4. **Divi Integration:**
   - While editing in the Divi builder, placeholders will be highlighted, making it easier to spot and manage your variables.

## Compatibility and Requirements

- **WordPress:** Tested with the latest WordPress versions.
- **Divi Builder:** Fully compatible and integrates directly.
- **PHP Version:** Works with PHP 7.0+.

## Support

If you encounter any issues or have questions, please open an issue on GitHub. Contributions and suggestions are welcome!

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
