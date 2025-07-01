import Model from '@ckeditor/ckeditor5-engine/src/model/model';
import ModelElement from '@ckeditor/ckeditor5-engine/src/model/element';
import ModelSchema from '@ckeditor/ckeditor5-engine/src/model/schema';
import ModelSelection from '@ckeditor/ckeditor5-engine/src/model/selection';
import ModelDocumentSelection from '@ckeditor/ckeditor5-engine/src/model/documentselection';

import Command from '@ckeditor/ckeditor5-core/src/command';
import { findOptimalInsertionRange } from '@ckeditor/ckeditor5-widget/src/utils';

/**
 * The horizontal line command.
 */
export class HorizontalLineCommand extends Command {
	public override refresh(): void {
		const model = this.editor.model;
		const schema = model.schema;
		const selection = model.document.selection;

		this.isEnabled = isHorizontalLineAllowedInParent(selection, schema, model);
	}

	public override execute(): void {
		const model = this.editor.model;

		model.change(writer => {
			const horizontalElement = writer.createElement('horizontalLine');
			model.insertObject(horizontalElement, null, null, { setSelection: 'after' });
		});
	}
}

function isHorizontalLineAllowedInParent(selection: ModelSelection | ModelDocumentSelection, schema: ModelSchema, model: Model): boolean {
	const parent = getInsertHorizontalLineParent(selection, model);
	return schema.checkChild(parent, 'horizontalLine');
}

function getInsertHorizontalLineParent(selection: ModelSelection | ModelDocumentSelection, model: Model): ModelElement {
	const insertionRange = findOptimalInsertionRange(selection, model);
	const parent = insertionRange.start.parent;

	if (parent.isEmpty && !parent.is('element', '$root')) {
		return parent.parent! as ModelElement;
	}

	return parent as ModelElement;
}
