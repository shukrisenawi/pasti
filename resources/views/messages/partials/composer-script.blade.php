<script>
    window.messageComposer = window.messageComposer || function (initialBody = '') {
        return {
            body: initialBody,
            emojiOpen: false,
            attachmentName: '',
            attachmentPreviewUrl: null,
            attachmentIsImage: false,
            viewportSyncRegistered: false,
            emojis: ['😀', '😁', '😂', '😊', '😍', '🥰', '😘', '🤗', '🤔', '😎', '🥳', '🙏', '👍', '👏', '💪', '🔥', '🌟', '❤️', '💚', '💙', '🎉', '📌', '📣', '✅'],
            init() {
                this.registerViewportSync();
            },
            hasVariableToken() {
                return this.body.includes('@nama') || this.body.includes('@pasti');
            },
            handleComposerFocus() {
                this.syncChatToLatest();
                this.syncChatToLatest(140);
                this.syncChatToLatest(320);
            },
            registerViewportSync() {
                if (this.viewportSyncRegistered || ! window.visualViewport) {
                    return;
                }

                this.viewportSyncRegistered = true;

                const syncIfFocused = () => {
                    if (document.activeElement !== this.$refs.textarea) {
                        return;
                    }

                    this.syncChatToLatest(40);
                    this.syncChatToLatest(180);
                };

                window.visualViewport.addEventListener('resize', syncIfFocused);
                window.visualViewport.addEventListener('scroll', syncIfFocused);
            },
            syncChatToLatest(delay = 0) {
                const run = () => {
                    const chatScroller = document.querySelector('[x-ref="chatScroller"]');

                    if (! chatScroller) {
                        return;
                    }

                    chatScroller.scrollTop = chatScroller.scrollHeight;

                    const entries = chatScroller.querySelectorAll('[data-chat-entry]');
                    const latestEntry = entries[entries.length - 1];

                    latestEntry?.scrollIntoView({ block: 'end' });
                };

                if (delay > 0) {
                    window.setTimeout(run, delay);
                    return;
                }

                this.$nextTick(run);
            },
            previewHtml() {
                return this.escapeHtml(this.body)
                    .replace(/@nama/g, this.badgeHtml('@nama'))
                    .replace(/@pasti/g, this.badgeHtml('@pasti'))
                    .replace(/\n/g, '<br>');
            },
            badgeHtml(label) {
                return `<span class="mx-0.5 inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">${label}</span>`;
            },
            insertEmoji(emoji) {
                const textarea = this.$refs.textarea;

                if (! textarea) {
                    this.body += emoji;
                    this.emojiOpen = false;
                    return;
                }

                const start = textarea.selectionStart ?? this.body.length;
                const end = textarea.selectionEnd ?? this.body.length;

                this.body = this.body.slice(0, start) + emoji + this.body.slice(end);

                this.$nextTick(() => {
                    textarea.focus();
                    const nextPosition = start + emoji.length;
                    textarea.setSelectionRange(nextPosition, nextPosition);
                });

                this.emojiOpen = false;
            },
            previewAttachment(event) {
                const input = event?.target;
                const file = input?.files?.[0];

                this.resetAttachmentPreview();

                if (! file) {
                    return;
                }

                this.attachmentName = file.name;
                this.attachmentIsImage = typeof file.type === 'string' && file.type.startsWith('image/');

                if (this.attachmentIsImage) {
                    this.attachmentPreviewUrl = URL.createObjectURL(file);
                }
            },
            clearAttachment() {
                if (this.$refs.attachmentInput) {
                    this.$refs.attachmentInput.value = '';
                }

                this.resetAttachmentPreview();
            },
            resetAttachmentPreview() {
                if (this.attachmentPreviewUrl) {
                    URL.revokeObjectURL(this.attachmentPreviewUrl);
                }

                this.attachmentName = '';
                this.attachmentPreviewUrl = null;
                this.attachmentIsImage = false;
            },
            escapeHtml(value) {
                return value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            },
        };
    };
</script>
