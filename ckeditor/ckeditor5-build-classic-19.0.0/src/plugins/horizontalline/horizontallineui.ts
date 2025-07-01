/**
 * @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */

/**
 * @module horizontal-line/horizontallineui
 */

import { Plugin } from '@ckeditor/ckeditor5-core';
import { ButtonView } from '@ckeditor/ckeditor5-ui';
import type { Editor } from '@ckeditor/ckeditor5-core';
import type { Locale } from '@ckeditor/ckeditor5-utils';

import horizontalLineIcon from '../../icons/horizontalline.svg';

/**
 * The horizontal line UI plugin.
 */
export default class HorizontalLineUI extends Plugin {
                    public readonly editor!: Editor;  // ? 이 줄을 바로 아래에 추가
             public static get pluginName(): 'HorizontalLineUI' {
		return 'HorizontalLineUI';
	}

	public init(): void {
		const editor = this.editor;

		editor.ui.componentFactory.add( 'horizontalLine', ( locale: Locale ) => {

			const command = editor.commands.get( 'horizontalLine' );
			const view = new ButtonView( locale );

			view.set( {
				label: 'Horizontal line',
				icon: horizontalLineIcon,
				tooltip: true
			} );

			view.bind( 'isEnabled' ).to( command!, 'isEnabled' );

			( this as any ).listenTo( view, 'execute', () => {
				editor.execute( 'horizontalLine' );
				editor.editing.view.focus();
			} );

			return view;
		} );
	}
}

declare module '@ckeditor/ckeditor5-core' {
	interface PluginsMap {
		[ 'HorizontalLineUI' ]: HorizontalLineUI;
	}
}

