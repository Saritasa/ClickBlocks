/**
 * Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/* exported initSample */

if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
	CKEDITOR.tools.enableHtml5Elements( document );

// The trick to keep the editor in the sample quite small
// unless user specified own height.
CKEDITOR.config.height = 'auto';
CKEDITOR.config.width = 'auto';

CKEDITOR.config.toolbar = [];
CKEDITOR.config.scayt_autoStartup = false;
CKEDITOR.config.startupFocus = false;
CKEDITOR.config.autoGrow_onStartup = true;
CKEDITOR.config.extraPlugins = 'autogrow';
CKEDITOR.config.autoGrow_minHeight = 200;
//CKEDITOR.config.autoGrow_maxHeight = 600;
CKEDITOR.config.autoGrow_bottomSpace = 50;

//CKEDITOR.editor.ui.space( 'contents' ).getStyle( 'height' );


var initSample = ( function() {
	var wysiwygareaAvailable = isWysiwygareaAvailable(),
		isBBCodeBuiltIn = !!CKEDITOR.plugins.get( 'bbcode' );

	return function() {
		var editorElement = CKEDITOR.document.getById( 'editor' );

		// :(((
		if ( isBBCodeBuiltIn ) {
			editorElement.setHtml(
				'Hello world!\n\n' +
				'I\'m an instance of [url=http://ckeditor.com]CKEditor[/url].'
			);
		}

		// Depending on the wysiwygare plugin availability initialize classic or inline editor.
		if ( wysiwygareaAvailable ) {
			CKEDITOR.replace( 'editor' );
		} else {
			editorElement.setAttribute( 'contenteditable', 'true' );
			CKEDITOR.inline( 'editor' );

			// TODO we can consider displaying some info box that
			// without wysiwygarea the classic editor may not work.
		}
	};

	function isWysiwygareaAvailable() {
		// If in development mode, then the wysiwygarea must be available.
		// Split REV into two strings so builder does not replace it :D.
		if ( CKEDITOR.revision == ( '%RE' + 'V%' ) ) {
			return true;
		}

		return !!CKEDITOR.plugins.get( 'wysiwygarea' );
	}
} )();

$(function() {

    function autoHeightCKEditor() {
        setTimeout(function () {
            var ckeditorFrame = $('#cke_1_contents iframe');
            console.log(ckeditorFrame.height());
            var innerDoc = (ckeditorFrame.get(0).contentDocument) ? ckeditorFrame.get(0).contentDocument : ckeditorFrame.get(0).contentWindow.document;
            $('#cke_1_contents').height($(innerDoc.body).height() + 100);
        }, 500);
    }

    $(window).on('load', function() {
       // autoHeightCKEditor();
    });


    $(document).ready(function(){
// add editor
//         editor = CKEDITOR.replace('content', {
//             height: ($(document).height() - 150) + 'px'
//         });

        CKEDITOR.on("instanceReady", function(event)
        {
console.log(this);
        });


        // var ckframe = $(editor.document.getWindow().$.frameElement.contentDocument);
        // ckframe.blur(function () {
        //     //saveValue();
        // });
    });
});

