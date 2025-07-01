/**
 * @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
/**
 * @module horizontal-line/horizontallineui
 */
import { Plugin } from '@ckeditor/ckeditor5-core';
import { ButtonView } from '@ckeditor/ckeditor5-ui';
import horizontalLineIcon from '../../icons/horizontalline.svg';
/**
 * The horizontal line UI plugin.
 */
var HorizontalLineUI = /** @class */ (function (_super) {
    __extends(HorizontalLineUI, _super);
    function HorizontalLineUI() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    Object.defineProperty(HorizontalLineUI, "pluginName", {
        get: function () {
            return 'HorizontalLineUI';
        },
        enumerable: false,
        configurable: true
    });
    HorizontalLineUI.prototype.init = function () {
        var _this = this;
        var editor = this.editor;
        editor.ui.componentFactory.add('horizontalLine', function (locale) {
            var command = editor.commands.get('horizontalLine');
            var view = new ButtonView(locale);
            view.set({
                label: 'Horizontal line',
                icon: horizontalLineIcon,
                tooltip: true
            });
            view.bind('isEnabled').to(command, 'isEnabled');
            _this.listenTo(view, 'execute', function () {
                editor.execute('horizontalLine');
                editor.editing.view.focus();
            });
            return view;
        });
    };
    return HorizontalLineUI;
}(Plugin));
export default HorizontalLineUI;
