import {
  InspectorControls,
  BlockControls,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  Placeholder,
  RadioControl,
  RangeControl,
  Spinner,
  ToggleControl,
  ToolbarGroup,
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
} from "@wordpress/components";
import { __, _x } from "@wordpress/i18n";
import { useSelect } from "@wordpress/data";
import { store as coreStore } from "@wordpress/core-data";
import { postList } from "@wordpress/icons";

/* Block name */
export const name = `radicle/latest-seeds`;

/* Block title */
export const title = __(`Latest Seeds`, `radicle`);

/* Block category */
export const category = `widgets`;

/* Block icon */
export const icon = postList;

/* Block attributes */
export const attributes = {
  posts: {
    type: 'number',
    default: 5,
  },
  displayPostContent: {
    type: 'string',
    default: 'none',
  },
  postLayout: {
    type: 'string',
    default: 'list',
  },
  displayFeaturedImage: {
    type: 'boolean',
    default: false,
  },
};

function getFeaturedImageDetails(post) {
  const image = post._embedded?.['wp:featuredmedia']?.[0];
  return {
    url: image?.source_url,
    alt: image?.alt_text,
  };
}

function Controls({ attributes, setAttributes }) {
  const {
    posts,
    displayFeaturedImage,
    displayPostContent,
    postLayout,
  } = attributes;

  return (
    <>
      <ToolsPanel
        label={__('Settings')}
        resetAll={() =>
          setAttributes({
            posts: 5,
            displayPostContent: 'none',
            postLayout: 'list',
            displayFeaturedImage: false,
          })
        }
      >
        <ToolsPanelItem
          hasValue={() => posts !== 5}
          label={__('Number of seeds')}
          onDeselect={() => setAttributes({ posts: 5 })}
          isShownByDefault
        >
          <RangeControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={__('Number of seeds')}
            value={posts}
            onChange={(value) => setAttributes({ posts: value })}
            min={1}
            max={10}
          />
        </ToolsPanelItem>

        <ToolsPanelItem
          hasValue={() => postLayout !== 'list'}
          label={__('Layout')}
          onDeselect={() => setAttributes({ postLayout: 'list' })}
          isShownByDefault
        >
          <RadioControl
            label={__('Layout')}
            selected={postLayout}
            options={[
              { label: __('List'), value: 'list' },
              { label: __('Grid'), value: 'grid' },
            ]}
            onChange={(value) => setAttributes({ postLayout: value })}
          />
        </ToolsPanelItem>

        <ToolsPanelItem
          hasValue={() => !!displayFeaturedImage}
          label={__('Display featured image')}
          onDeselect={() => setAttributes({ displayFeaturedImage: false })}
          isShownByDefault
        >
          <ToggleControl
            __nextHasNoMarginBottom
            label={__('Display featured image')}
            checked={displayFeaturedImage}
            onChange={(value) => setAttributes({ displayFeaturedImage: value })}
          />
        </ToolsPanelItem>

        <ToolsPanelItem
          hasValue={() => displayPostContent !== 'none'}
          label={__('Post content')}
          onDeselect={() => setAttributes({ displayPostContent: 'none' })}
          isShownByDefault
        >
          <RadioControl
            label={__('Post content')}
            selected={displayPostContent}
            options={[
              { label: __('None'), value: 'none' },
              { label: __('Excerpt'), value: 'excerpt' },
              { label: __('Content'), value: 'content' },
            ]}
            onChange={(value) => setAttributes({ displayPostContent: value })}
          />
        </ToolsPanelItem>
      </ToolsPanel>
    </>
  );
}

/* Block edit */
export const edit = ({ attributes, setAttributes }) => {

  const {
    posts,
    displayFeaturedImage,
    displayPostContent,
    postLayout,
  } = attributes;

  const { latestSeeds } = useSelect((select) => {
    const { getEntityRecords } = select(coreStore);
    const latestSeedsQuery = {
      per_page: posts,
      _embed: 'wp:featuredmedia',
      order: 'desc',
      orderby: 'date',
    };

    return {
      latestSeeds: getEntityRecords('postType', 'seed', latestSeedsQuery),
    };
  }, [posts]);

  const handleLinkClick = (event) => {
    event.preventDefault();
  };

  const hasPosts = !!latestSeeds?.length;
  const inspectorControls = (
    <InspectorControls>
      <Controls
        attributes={attributes}
        setAttributes={setAttributes}
      />
    </InspectorControls>
  );

  const containerClass = postLayout === 'grid'
    ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6'
    : 'list-none p-0 m-0 space-y-6';

  const blockProps = useBlockProps({
    className: containerClass,
  });

  if (!hasPosts) {
    return (
      <div {...blockProps}>
        {inspectorControls}
        <Placeholder label={__('Latest Seeds')}>
          {!Array.isArray(latestSeeds) ? <Spinner /> : __('No seeds found.')}
        </Placeholder>
      </div>
    );
  }

  const displayPosts = latestSeeds.length > posts ? latestSeeds.slice(0, posts) : latestSeeds;

  const layoutControls = [
    {
      title: _x('List view', 'Latest seeds block display setting'),
      onClick: () => setAttributes({ postLayout: 'list' }),
      isActive: postLayout === 'list',
    },
    {
      title: _x('Grid view', 'Latest seeds block display setting'),
      onClick: () => setAttributes({ postLayout: 'grid' }),
      isActive: postLayout === 'grid',
    },
  ];

  const ElementType = postLayout === 'grid' ? 'div' : 'ul';
  const ItemElement = postLayout === 'grid' ? 'article' : 'li';

  return (
    <>
      {inspectorControls}
      <BlockControls>
        <ToolbarGroup controls={layoutControls} />
      </BlockControls>
      <ElementType {...blockProps} role="feed" aria-label="Latest seeds">
        {displayPosts.map((post) => {
          const titleTrimmed = post.title?.rendered?.trim() || 'Untitled';
          let excerpt = post.excerpt?.rendered || '';

          if (excerpt) {
            try {
              const excerptElement = document.createElement('div');
              excerptElement.innerHTML = excerpt;
              excerpt = excerptElement.textContent || excerptElement.innerText || '';
            } catch (e) {
              excerpt = '';
            }
          }

          const { url: imageSourceUrl, alt: featuredImageAlt } = getFeaturedImageDetails(post);
          const renderFeaturedImage = displayFeaturedImage && imageSourceUrl;

          const itemClasses = postLayout === 'grid'
            ? 'bg-white border border-gray-200 overflow-hidden'
            : 'pb-6 border-b border-gray-200 last:border-b-0 last:pb-0 flex gap-4 items-start';

          const imageClasses = postLayout === 'grid'
            ? 'w-full h-48 object-cover'
            : 'w-32 h-24 object-cover flex-shrink-0';

          const titleClasses = postLayout === 'grid'
            ? 'block text-lg font-semibold text-gray-900 hover:text-blue-600 no-underline mb-2 p-4 pb-2'
            : 'block text-lg font-semibold text-gray-900 hover:text-blue-600 no-underline mb-2';

          const contentClasses = postLayout === 'grid'
            ? 'text-gray-600 text-sm leading-relaxed px-4 pb-4'
            : 'text-gray-600 text-sm leading-relaxed';

          const content = (
            <>
              {renderFeaturedImage && (
                <figure className={postLayout === 'grid' ? '' : 'mb-4'}>
                  <img
                    src={imageSourceUrl}
                    alt={featuredImageAlt || titleTrimmed}
                    className={imageClasses}
                    loading="lazy"
                    decoding="async"
                  />
                </figure>
              )}
              <a
                className={titleClasses}
                href={post.link || '#'}
                onClick={handleLinkClick}
                id={`seed-title-${post.id}`}
                aria-describedby={displayPostContent !== 'none' ? `seed-content-${post.id}` : undefined}
              >
                {titleTrimmed}
              </a>

              {displayPostContent === 'excerpt' && excerpt && (
                <div className={contentClasses} id={`seed-content-${post.id}`}>
                  {excerpt}
                </div>
              )}

              {displayPostContent === 'content' && (
                <div
                  className={contentClasses.replace('text-gray-600', 'text-gray-800')}
                  id={`seed-content-${post.id}`}
                  dangerouslySetInnerHTML={{
                    __html: post.content?.rendered || '',
                  }}
                />
              )}
            </>
          );

          return postLayout === 'grid' ? (
            <ItemElement
              key={post.id}
              className={itemClasses}
              aria-labelledby={`seed-title-${post.id}`}
            >
              {content}
            </ItemElement>
          ) : (
            <ItemElement key={post.id} className={itemClasses}>
              {renderFeaturedImage && (
                <figure>
                  <img
                    src={imageSourceUrl}
                    alt={featuredImageAlt || titleTrimmed}
                    className={imageClasses}
                    loading="lazy"
                    decoding="async"
                  />
                </figure>
              )}
              <article aria-labelledby={`seed-title-${post.id}`} className="flex-1 min-w-0">
                <a
                  className={titleClasses}
                  href={post.link || '#'}
                  onClick={handleLinkClick}
                  id={`seed-title-${post.id}`}
                  aria-describedby={displayPostContent !== 'none' ? `seed-content-${post.id}` : undefined}
                >
                  {titleTrimmed}
                </a>

                {displayPostContent === 'excerpt' && excerpt && (
                  <div className={contentClasses} id={`seed-content-${post.id}`}>
                    {excerpt}
                  </div>
                )}

                {displayPostContent === 'content' && (
                  <div
                    className={contentClasses.replace('text-gray-600', 'text-gray-800')}
                    id={`seed-content-${post.id}`}
                    dangerouslySetInnerHTML={{
                      __html: post.content?.rendered || '',
                    }}
                  />
                )}
              </article>
            </ItemElement>
          );
        })}
      </ElementType>
    </>
  );
};

/* Block save */
export const save = () => null;
