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
 * @module horizontal-line/horizontalline
 */
import { Plugin } from 'ckeditor5/src/core.js';
import { Widget } from 'ckeditor5/src/widget.js';
import { HorizontalLineEditing } from './horizontallineediting.js';
import { HorizontalLineUI } from './horizontallineui.js';
/**
 * The horizontal line feature.
 *
 * It provides the possibility to insert a horizontal line into the rich-text editor.
 *
 * For a detailed overview, check the {@glink features/horizontal-line Horizontal line feature} documentation.
 */
var HorizontalLine = /** @class */ (function (_super) {
    __extends(HorizontalLine, _super);
    function HorizontalLine() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    Object.defineProperty(HorizontalLine, "requires", {
        /**
         * @inheritDoc
         */
        get: function () {
            return [HorizontalLineEditing, HorizontalLineUI, Widget];
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(HorizontalLine, "pluginName", {
        /**
         * @inheritDoc
         */
        get: function () {
            return 'HorizontalLine';
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(HorizontalLine, "isOfficialPlugin", {
        /**
         * @inheritDoc
         */
        get: function () {
            return true;
        },
        enumerable: false,
        configurable: true
    });
    return HorizontalLine;
}(Plugin));
export { HorizontalLine };
// ? �� �� �߰�!
export default HorizontalLine;
