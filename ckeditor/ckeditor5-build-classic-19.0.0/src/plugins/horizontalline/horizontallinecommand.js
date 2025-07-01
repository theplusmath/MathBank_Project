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
import Command from '@ckeditor/ckeditor5-core/src/command';
import { findOptimalInsertionRange } from '@ckeditor/ckeditor5-widget/src/utils';
/**
 * The horizontal line command.
 */
var HorizontalLineCommand = /** @class */ (function (_super) {
    __extends(HorizontalLineCommand, _super);
    function HorizontalLineCommand() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    HorizontalLineCommand.prototype.refresh = function () {
        var model = this.editor.model;
        var schema = model.schema;
        var selection = model.document.selection;
        this.isEnabled = isHorizontalLineAllowedInParent(selection, schema, model);
    };
    HorizontalLineCommand.prototype.execute = function () {
        var model = this.editor.model;
        model.change(function (writer) {
            var horizontalElement = writer.createElement('horizontalLine');
            model.insertObject(horizontalElement, null, null, { setSelection: 'after' });
        });
    };
    return HorizontalLineCommand;
}(Command));
export { HorizontalLineCommand };
function isHorizontalLineAllowedInParent(selection, schema, model) {
    var parent = getInsertHorizontalLineParent(selection, model);
    return schema.checkChild(parent, 'horizontalLine');
}
function getInsertHorizontalLineParent(selection, model) {
    var insertionRange = findOptimalInsertionRange(selection, model);
    var parent = insertionRange.start.parent;
    if (parent.isEmpty && !parent.is('element', '$root')) {
        return parent.parent;
    }
    return parent;
}
