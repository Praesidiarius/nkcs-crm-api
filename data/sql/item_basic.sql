--
-- forms
--
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`) VALUES
  (NULL, 'item.item', 'item');
COMMIT;
SET @last_id_in_dynamic_form = LAST_INSERT_ID();

--
-- form sections
--
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`) VALUES
  (NULL, NULL, 'item.item', 'itemMain', 4);
COMMIT;

SET @last_id_in_dynamic_form_section = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`) VALUES
  (NULL, @last_id_in_dynamic_form_section, 'item.form.section.basic', 'itemBasic', @last_id_in_dynamic_form);
COMMIT;

--
-- form fields
--
