import domReady from '@wordpress/dom-ready';
import { registerBlockType } from "@wordpress/blocks";
import * as modalBlock from "./editor/modal.block";
import * as latestSeedsBlock from "./editor/latest-seeds.block";

domReady(() => {
  /**
   * Register blocks with their configurations
   */
  const blocks = [
    modalBlock,
    latestSeedsBlock,
  ];

  blocks.forEach(block => {
    registerBlockType(block.name, {
      apiVersion: 3,
      title: block.title,
      category: block.category,
      icon: block.icon,
      attributes: block.attributes,
      edit: block.edit,
      save: block.save,
    });
  });
});
