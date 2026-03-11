(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { MediaUpload, MediaUploadCheck, InspectorControls } = wp.blockEditor || wp.editor;
    const { Button, PanelBody } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    const { __ } = wp.i18n;

    registerBlockType('tp/slider-oriz', {
        title: __('Slider Orizzontale', 'tp-slider-oriz'),
        icon: 'images-alt2',
        category: 'media',
        attributes: {
            images: {
                type: 'array',
                default: []
            }
        },
        edit: function (props) {
            const images = props.attributes.images || [];

            function onSelectImages(newImages) {
                props.setAttributes({
                    images: newImages.map(function (img) {
                        return {
                            url: img.url,
                            alt: img.alt,
                            id: img.id,
                            thumb: img.sizes && img.sizes.thumbnail ? img.sizes.thumbnail.url : img.url
                        };
                    })
                });
            }

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Immagini slider', 'tp-slider-oriz'), initialOpen: true },
                        el(
                            MediaUploadCheck,
                            {},
                            el(MediaUpload, {
                                onSelect: onSelectImages,
                                allowedTypes: ['image'],
                                multiple: true,
                                gallery: true,
                                value: images.map(function (img) { return img.id; }),
                                render: function ({ open }) {
                                    return el(Button, { onClick: open, isPrimary: true }, __('Scegli immagini', 'tp-slider-oriz'));
                                }
                            })
                        )
                    )
                ),
                el(
                    'div',
                    { className: 'tp-slider-oriz-preview' },
                    images.length > 0
                        ? el(
                            'ul',
                            {
                                style: {
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: '0.5rem',
                                    padding: 0,
                                    listStyle: 'none'
                                }
                            },
                            images.map(function (img, i) {
                                return el('li', { key: i },
                                    el('img', {
                                        src: img.thumb || img.url,
                                        alt: img.alt || '',
                                        style: {
                                            maxWidth: '100px',
                                            height: 'auto',
                                            border: '1px solid #ccc',
                                            borderRadius: '4px'
                                        }
                                    })
                                );
                            })
                        )
                        : el('p', {}, __('Nessuna immagine selezionata', 'tp-slider-oriz'))
                )
            );
        },
        save: function () {
            return null; // Dynamic block
        }
    });
})(window.wp);