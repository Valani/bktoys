<?php
// Heading
$_["heading_title"] = "CSV Import";

// Text
$_["text_extension"] = "Extensions";
$_["text_success"] = "Success: You have modified CSV Import module!";
$_["text_edit"] = "Edit CSV Import Module";
$_["text_csv_booktoys_menu"] = "Import Book&Toys CSV";

// Entry
$_["entry_upd_name"] = "Update name";
$_["entry_upd_desc"] = "Update description";
$_["entry_upd_cat"] = "Update categories";
$_["entry_upd_attr"] = "Update characteristics";
$_["entry_upd_status"] = "Update status";
$_["entry_ignore_empty"] = "Ignore empty fields during import (do not overwrite existing data with empty cells)";

// Instructions
$_["text_instructions_title"] =
    'Instructions for Using the "csv_booktoys" Module';
$_["text_instructions_desc"] =
    "This module is designed for the rapid update of information regarding books and toys.";
$_["text_rule_1"] =
    "<b>First column (sku)</b> — must contain the product SKU. If the system finds a product with this SKU, it updates its information.";
$_["text_rule_2"] =
    "<b>Categories (category)</b> — multiple categories can be specified using a forward slash / (e.g., Books/Fairy Tales/For Toddlers). All categories must already be created on the website, otherwise, the module will skip them.";
$_["text_rule_3"] =
    "<b>Main category (main_category)</b> — specify the exact name of one of the categories so the website knows which one is primary for this product.";
$_["text_rule_4"] =
    "<b>Characteristics (Attributes)</b> — to add characteristics, the column name must start with the ATR- prefix. For example: ATR-FORMAT or ATR-WEIGHT. The attributes themselves must be pre-created in the Catalog -> Attributes menu.";
$_["text_rule_5"] = '<b>Status (status)</b> — column with values "on" or "off" to enable or disable products.';
$_["text_rule_footer"] =
    '<b>Flexible editing:</b> Use the checkboxes below to select exactly which data should be updated for existing products on the site. If you select "Ignore empty fields during import", empty cells in your CSV will be skipped, ensuring you don\'t accidentally overwrite existing product data or attributes with blanks. Settings are saved automatically.';

// Button
$_["button_import"] = "Import File";

// Error
$_["error_permission"] =
    "Warning: You do not have permission to modify CSV Import module!";
$_["error_upload"] = "Please select a CSV file to upload!";
$_["error_filetype"] = "Invalid file type. Please upload a CSV file.";
