/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Select, registry) {
    'use strict';

    return Select.extend({
        defaults: {
            listens: {
                value: 'changeTypeUpload'
            },
            typeUrl: 'file',
            typeFile: 'link_url',
            typeEbook: 'editionguard_resource',
            filterPlaceholder: 'ns = ${ $.ns }, parentScope = ${ $.parentScope }'
        },

        /**
         * Initialize component.
         * @returns {Element}
         */
        initialize: function () {
            return this
                ._super()
                .changeTypeUpload(this.initialValue);
        },

        /**
         * Callback that fires when 'value' property is updated.
         *
         * @param {String} currentValue
         * @returns {*}
         */
        onUpdate: function (currentValue) {
                        
            this.changeTypeUpload(currentValue);
            return this._super();
        },

        /**
         * Change visibility for typeUrl/typeFile based on current value.
         *
         * @param {String} currentValue
         */
        changeTypeUpload: function (currentValue) {
                       
            var componentFile = this.filterPlaceholder + ', index=' + this.typeFile,
                componentUrl = this.filterPlaceholder + ', index=' + this.typeUrl,
                componentEbook = this.filterPlaceholder + ', index=' + this.typeEbook;
               
            switch (currentValue) {
                case 'file':
                    this.changeVisible(componentFile, true);
                    this.changeVisible(componentUrl, false);
                    this.changeVisible(componentEbook, false);
                    break;
                case 'url':
                    this.changeVisible(componentFile, false);
                    this.changeVisible(componentUrl, true);
                    this.changeVisible(componentEbook, false);
                    break;
                case 'ebook':
                    this.changeVisible(componentFile, false);
                    this.changeVisible(componentUrl, false);
                    this.changeVisible(componentEbook, true);
                    break;
            }
        },

        /**
         * Change visible
         *
         * @param {String} filter
         * @param {Boolean} visible
         */
        changeVisible: function (filter, visible) {
            registry.async(filter)(
                function (currentComponent) {
                    currentComponent.visible(visible);
                }
            );
        }
    });
});
