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
 * @module horizontal-line/horizontallineediting
 */
import { Plugin } from 'ckeditor5/src/core';
import { toWidget } from 'ckeditor5/src/widget';
import { HorizontalLineCommand } from './horizontallinecommand';
import '../theme/horizontalline.css';
/**
 * The horizontal line editing feature.
 */
var HorizontalLineEditing = /** @class */ (function (_super) {
    __extends(HorizontalLineEditing, _super);
    function HorizontalLineEditing() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    Object.defineProperty(HorizontalLineEditing, "pluginName", {
        get: function () {
            return 'HorizontalLineEditing';
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(HorizontalLineEditing, "isOfficialPlugin", {
        get: function () {
            return true;
        },
        enumerable: false,
        configurable: true
    });
    HorizontalLineEditing.prototype.init = function () {
        var editor = this.editor;
        var schema = editor.model.schema;
        var t = editor.t;
        var conversion = editor.conversion;
        schema.register('horizontalLine', {
            inheritAllFrom: '$blockObject'
        });
        conversion.for('dataDowncast').elementToElement({
            model: 'horizontalLine',
            view: function (_modelElement, _a) {
                var writer = _a.writer;
                return writer.createEmptyElement('hr');
            }
        });
        conversion.for('editingDowncast').elementToStructure({
            model: 'horizontalLine',
            view: function (_modelElement, _a) {
                var writer = _a.writer;
                var label = t('Horizontal line');
                var viewWrapper = writer.createContainerElement('div', null, writer.createEmptyElement('hr'));
                writer.addClass('ck-horizontal-line', viewWrapper);
                writer.setCustomProperty('hr', true, viewWrapper);
                return toHorizontalLineWidget(viewWrapper, writer, label);
            }
        });
        conversion.for('upcast').elementToElement({ view: 'hr', model: 'horizontalLine' });
        editor.commands.add('horizontalLine', new HorizontalLineCommand(editor));
    };
    return HorizontalLineEditing;
}(Plugin));
export { HorizontalLineEditing };
/**
 * Converts a given view element to a horizontal line widget.
 */
function toHorizontalLineWidget(viewElement, writer, label) {
    writer.setCustomProperty('horizontalLine', true, viewElement);
    return toWidget(viewElement, writer, { label: label });
}
