const { registerBlockType } = wp.blocks;
const { MediaUpload, InspectorControls, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, Button, RangeControl } = wp.components;
const { __ } = wp.i18n;
const { Fragment, useState, useEffect } = wp.element;

registerBlockType('tpdesign/tp-slider-vert', {
  title: __('TP Slider Verticale', 'tp-slider-vert'),
  icon: 'images-alt2',
  category: 'widgets',
  attributes: {
    firstSlider: { type: 'array', default: [] },
    secondSlider: { type: 'array', default: [] },
    thirdSlider: { type: 'array', default: [] },
    speed1: { type: 'number', default: 40 },
    speed2: { type: 'number', default: 45 },
    speed3: { type: 'number', default: 50 },
    mobileHeight: { type: 'number', default: 100 },
  },
  edit: function (props) {
    const { attributes, setAttributes } = props;

    const [thumbs, setThumbs] = useState({
      firstSlider: [],
      secondSlider: [],
      thirdSlider: []
    });

    const updateThumbnails = (sliderName, ids) => {
      Promise.all(
        ids.map(id => wp.media.attachment(id).fetch().then(() => {
          const att = wp.media.attachment(id);
          return att.get('sizes')?.thumbnail?.url || att.get('url') || '';
        }))
      ).then(urls => {
        setThumbs(prev => ({ ...prev, [sliderName]: urls }));
      });
    };

    useEffect(() => {
      if (attributes.firstSlider.length) updateThumbnails('firstSlider', attributes.firstSlider);
      if (attributes.secondSlider.length) updateThumbnails('secondSlider', attributes.secondSlider);
      if (attributes.thirdSlider.length) updateThumbnails('thirdSlider', attributes.thirdSlider);
    }, []);

    const updateSlider = function (sliderName, images) {
      const ids = images.map(img => img.id);
      setAttributes({ [sliderName]: ids });
      updateThumbnails(sliderName, ids);
    };

    const renderMediaUpload = function (sliderName, label) {
      return (
        wp.element.createElement('div', { style: { marginBottom: '1em' } },
          wp.element.createElement('div', {
            style: { marginBottom: '0.5em', display: 'flex', gap: '5px' }
          },
            thumbs[sliderName].map((url, index) => wp.element.createElement('img', {
              key: index,
              src: url,
              style: { height: '60px', width: 'auto', objectFit: 'cover' }
            }))
          ),
          wp.element.createElement(MediaUploadCheck, null,
            wp.element.createElement(MediaUpload, {
              onSelect: (imgs) => updateSlider(sliderName, imgs),
              allowedTypes: ['image'],
              multiple: true,
              gallery: true,
              value: attributes[sliderName],
              render: ({ open }) => wp.element.createElement(
                Button,
                { onClick: open, variant: 'secondary' },
                label
              )
            })
          )
        )
      );
    };

    return (
      wp.element.createElement(
        Fragment,
        null,
        wp.element.createElement(
          InspectorControls,
          null,
          wp.element.createElement(
            PanelBody,
            { title: __('Impostazioni Slider', 'tp-slider-vert') },
            wp.element.createElement(RangeControl, {
              label: __('Velocità colonna 1 (s)', 'tp-slider-vert'),
              min: 5,
              max: 100,
              step: 1,
              value: attributes.speed1,
              onChange: (value) => setAttributes({ speed1: value })
            }),
            wp.element.createElement(RangeControl, {
              label: __('Velocità colonna 2 (s)', 'tp-slider-vert'),
              min: 5,
              max: 100,
              step: 1,
              value: attributes.speed2,
              onChange: (value) => setAttributes({ speed2: value })
            }),
            wp.element.createElement(RangeControl, {
              label: __('Velocità colonna 3 (s)', 'tp-slider-vert'),
              min: 5,
              max: 100,
              step: 1,
              value: attributes.speed3,
              onChange: (value) => setAttributes({ speed3: value })
            }),
            wp.element.createElement(RangeControl, {
              label: __('Altezza immagini mobile (px)', 'tp-slider-vert'),
              min: 50,
              max: 300,
              step: 50,
              value: attributes.mobileHeight,
              onChange: (value) => setAttributes({ mobileHeight: value })
            })
          )
        ),
        wp.element.createElement(
          'div',
          { className: 'tp-slider-vert-block-admin' },
          renderMediaUpload('firstSlider', __('Seleziona immagini colonna 1', 'tp-slider-vert')),
          renderMediaUpload('secondSlider', __('Seleziona immagini colonna 2', 'tp-slider-vert')),
          renderMediaUpload('thirdSlider', __('Seleziona immagini colonna 3', 'tp-slider-vert'))
        )
      )
    );
  },
  save: function () {
    return null;
  }
});