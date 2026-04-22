<script>
    window.messageComposer = window.messageComposer || function (initialBody = '') {
        return {
            body: initialBody,
            emojiOpen: false,
            attachmentName: '',
            attachmentPreviewUrl: null,
            attachmentIsImage: false,
            emojis: ['😀', '😁', '😂', '😊', '😍', '🥰', '😘', '🤗', '🤔', '😎', '🥳', '🙏', '👍', '👏', '💪', '🔥', '🌟', '❤️', '💚', '💙', '🎉', '📌', '📣', '✅'],
            hasVariableToken() {
                return this.body.includes('@nama') || this.body.includes('@pasti');
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
