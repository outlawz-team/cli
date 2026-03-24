import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
} from "@wordpress/block-editor";
import { BaseControl, PanelBody, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { customPostType } from "@wordpress/icons";

/* Block name */
export const name = `radicle/modal`;

/* Block title */
export const title = __(`Modal`, `radicle`);

/* Block category */
export const category = `design`;

/* Block icon */
export const icon = customPostType;

/* Block attributes */
export const attributes = {
  heading: {
    type: `string`,
    default: __(`Modal heading`, `radicle`),
  },
  buttonText: {
    type: `string`,
    default: __(`Open modal`, `radicle`),
  },
};

/* Block edit */
export const edit = ({ attributes, setAttributes }) => {
  const props = useBlockProps({
    className: `border rounded-md p-3`,
  });

  const { buttonText, heading } = attributes;

  return (
    <>
      <InspectorControls>
        <PanelBody title={__(`Modal settings`, `radicle`)}>
          <TextControl
            label={__(`Modal button text`, `radicle`)}
            value={buttonText}
            onChange={(buttonText) => setAttributes({ buttonText })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...props}>
        <TextControl
          label={__(`Modal heading`, `radicle`)}
          className="mb-6"
          value={heading}
          onChange={(heading) => setAttributes({ heading })}
        />

        <BaseControl label={__(`Modal content`, `radicle`)} id="modal-content">
          <div className="bg-gray-100 p-3 rounded-md">
            <InnerBlocks
              template={[[`core/paragraph`]]}
              allowedBlocks={[`core/paragraph`, `core/heading`, `core/list`]}
            />
          </div>
        </BaseControl>
      </div>
    </>
  );
};

/* Block save */
export const save = () => <InnerBlocks.Content />
