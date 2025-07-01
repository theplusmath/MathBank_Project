/**
 * @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */

/**
 * @module horizontal-line/horizontallineediting
 */

import { Plugin } from 'ckeditor5/src/core';
import { toWidget } from 'ckeditor5/src/widget';
import type { DowncastWriter, ViewElement } from 'ckeditor5/src/engine';

import { HorizontalLineCommand } from './horizontallinecommand';

import '../theme/horizontalline.css';

/**
 * The horizontal line editing feature.
 */
export class HorizontalLineEditing extends Plugin {
	public static get pluginName() {
		return 'HorizontalLineEditing' as const;
	}

	public static override get isOfficialPlugin(): true {
		return true;
	}

	public init(): void {
		const editor = this.editor;
		const schema = editor.model.schema;
		const t = editor.t;
		const conversion = editor.conversion;

		schema.register('horizontalLine', {
			inheritAllFrom: '$blockObject'
		});

		conversion.for('dataDowncast').elementToElement({
			model: 'horizontalLine',
			view: (_modelElement, { writer }) => {
				return writer.createEmptyElement('hr');
			}
		});

		conversion.for('editingDowncast').elementToStructure({
			model: 'horizontalLine',
			view: (_modelElement, { writer }) => {
				const label = t('Horizontal line');

				const viewWrapper = writer.createContainerElement('div', null,
					writer.createEmptyElement('hr')
				);

				writer.addClass('ck-horizontal-line', viewWrapper);
				writer.setCustomProperty('hr', true, viewWrapper);

				return toHorizontalLineWidget(viewWrapper, writer, label);
			}
		});

		conversion.for('upcast').elementToElement({ view: 'hr', model: 'horizontalLine' });

		editor.commands.add('horizontalLine', new HorizontalLineCommand(editor));
	}
}

/**
 * Converts a given view element to a horizontal line widget.
 */
function toHorizontalLineWidget(viewElement: ViewElement, writer: DowncastWriter, label: string): ViewElement {
	writer.setCustomProperty('horizontalLine', true, viewElement);
	return toWidget(viewElement, writer, { label });
}
