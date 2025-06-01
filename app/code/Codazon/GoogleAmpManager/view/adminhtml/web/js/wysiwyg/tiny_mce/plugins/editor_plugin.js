/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
if (window.cdzAmpFunction === undefined) {
    window.cdzAmpFunction = function(tinymce) {
        tinymce.PluginManager.add('cdzampimage', function(editor, url) {
            var self = {
                ampPlaceholder: '\n<div id="amp-content-placeholder" style="display: none;">&nbsp;</div>',
                ampPlaceholder2: '\n<div id="amp-content-placeholder" style="display: none;"></div>',
                ampPlaceholderId: 'amp-content-placeholder',
                bookmark: '<span id="mce_marker" data-mce-type="bookmark">﻿</span>',
                decodeImages: function(content) {
                    if (content.includes(self.ampPlaceholderId)) {
                        return content.replace(/<img(.*?)>/gi, function(match) {
                            if (match.search(' id=') === -1) {
                                var attr = (match.search('data-mce-layout') == -1) ? ' layout="responsive" ' : '';
                                return match.replace('<img', '<amp-img')
                                    .replace('>', attr + '></amp-img>')
                                    .replace(' data-mce-layout=', ' layout=').replace(' data-mce-id=', ' id=');
                            } else {
                                return match;
                            }
                        }).replaceAll(self.ampPlaceholder, '').replaceAll(self.ampPlaceholder2, '');
                    }
                    return content;
                }
            };

            if (editor.id.includes("_amp_")) {
                editor.on('BeforeSetContent', function(e) {
                    if (e.target.id == editor.id) {
                        var content = e.content;
                        if (!content.includes(self.ampPlaceholderId)) {
                            if ((content != self.bookmark) && (content != '')) {
                                content = content.replace(/<amp-img(.*?)\/amp-img>/gi, function (match) {
                                    return match.replace('<amp-img', '<img')
                                        .replace('></amp-img>', '>')
                                        .replace(' layout=', ' data-mce-layout=')
                                        .replace(' id=', ' data-mce-id=');
                                }) + self.ampPlaceholder;
                                e.content = content;
                            }
                        }
                    }
                });
                varienGlobalEvents.attachEventHandler('wysiwygDecodeContent', function (content) {
                    return self.decodeImages(content);
                });
            }
        });
    };
}

if (require.toUrl('tinymce').includes('tinymce.min')) {
    require(['tinymce'], function(tinymce) {
        window.cdzAmpFunction(tinymce);
    });
} else {
    require(['tiny_mce_4/tinymce.min'], function(tinymce) {
        window.cdzAmpFunction(tinymce);
    });
}
