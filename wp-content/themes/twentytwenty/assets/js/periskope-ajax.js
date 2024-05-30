jQuery(document).ready(function($) {
    var offset = 0;
    var limit = 10;
    var loading = false;
    var allLoaded = false;
    var selectedHashtags = [];

    // Initialize Select2
    $('#hashtag-filter').select2({
        placeholder: 'Type to search hashtags...'
    });

    // Event listener for changes in Select2
    $('#hashtag-filter').on('change', function(e) {
        selectedHashtags = $(this).val();
        offset = 0;
        allLoaded = false;
        $('#chat-container').empty();
        loadMessages();
    });

    function loadMessages(delay = 0) {
        if (loading || allLoaded) return;
        loading = true;

        if (delay > 0) {
            // Show loading message
            $('#chat-container').append('<div id="loading">Loading more messages...</div>');
        }

        setTimeout(function() {
            $.ajax({
                url: periskope_ajax_obj.ajax_url,
                method: 'GET',
                data: {
                    offset: offset,
                    limit: limit,
                    hashtags: !selectedHashtags ? "" : selectedHashtags.join(',')
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', periskope_ajax_obj.nonce);
                },
                success: function(data) {
                    if (data.length < limit) {
                        allLoaded = true;
                    }

                    if (data.length === 0) {
                        loading = false;
                        $('#loading').remove(); // Remove loading message
                        return;
                    }

                    var chatContainer = $('#chat-container');
                    var storageDomain = "";

                    data.forEach(function(row) {
                        var messageClass = row.from_me == 1 ? 'sent' : 'received';
                        var quoteContent = '';
                        var mediaContent = '';

                        // Parse and format the author phone number
                        var author = formatPhoneNumber(row.author);
                        var timestamp = formatTimestamp(row.timestamp);

                        if (row.has_quoted_msg !== "0" && row.quote_message_id) {
                            var quoteAuthor = formatPhoneNumber(row.quote_author);
                            quoteContent = `<a href="#${row.quote_message_id}" class="blockquote-link"><blockquote>
                                <div class="author">${quoteAuthor}</div>
                            `;
                            if (row.quote_body) {
                                quoteContent += `<div class="body">${row.quote_body}</div>`;
                            } else if (row.quote_has_media !== "0") {
                                const quoteMediaData = JSON.parse(row.quote_media);
                                if (quoteMediaData.mimetype.startsWith('image/')) {
                                    quoteContent += `<div class="body">Image</div>`;
                                } else if (quoteMediaData.mimetype.startsWith('audio/')) {
                                    quoteContent += `<div class="body">Audio</div>`;
                                }
                            }
                            quoteContent += `</blockquote></a>`;
                        }

                        if (row.has_media !== "0" && row.media) {
                            var mediaData = JSON.parse(row.media);
                            var mediaPath = storageDomain + mediaData.path;

                            if (mediaData.mimetype.startsWith('image/')) {
                                mediaContent = `<img src="${mediaPath}" alt="${mediaData.filename}" style="max-width: 100%; height: auto;" />`;
                            } else if (mediaData.mimetype.startsWith('audio/')) {
                                mediaContent = `<audio controls><source src="${mediaPath}" type="${mediaData.mimetype}">Your browser does not support the audio element.</audio>`;
                            }
                        }

                        var message = `
                            <div class="message ${messageClass}" id="${row.message_id}">
                                ${quoteContent}
                                <div class="author">${author}</div>
                                <div class="body">${row.body}</div>
                                ${mediaContent}
                                <div class="timestamp">${timestamp}</div>
                            </div>
                        `;
                        chatContainer.append(message);
                    });

                    offset += limit;
                    $('#loading').remove(); // Remove loading message
                    loading = false;
                },
                error: function(error) {
                    console.log(error);
                    $('#loading').remove(); // Remove loading message
                    loading = false;
                }
            });
        }, delay); // Delay in milliseconds
    }

    // Initial load without delay
    loadMessages();

    // Infinite scroll with delay for subsequent loads
    $('#chat-container').on('scroll', function() {
        if ($('#chat-container').scrollTop() + $('#chat-container').innerHeight() >= $('#chat-container')[0].scrollHeight) {
            loadMessages(1000); // 2-second delay
        }
    });

    function formatPhoneNumber(phone) {
        // Remove '@c.us'
        phone = phone.replace('@c.us', '');

        // Hide the last four digits
        var visiblePart = phone.slice(0, -4);
        var hiddenPart = '****';

        // Use the libphonenumber-js library to format the number
        var formattedPhone = '';

        try {
            // Parse the phone number using libphonenumber-js
            var phoneNumber = libphonenumber.parsePhoneNumberFromString(phone, 'International');
            if (phoneNumber) {
                // Get the formatted phone number in international format with spaces
                var formattedNumber = phoneNumber.formatInternational();

                // Replace the last four digits with ****
                formattedPhone = formattedNumber.replace(/(\d{4})$/, hiddenPart);
            } else {
                // If parsing fails, use a default format
                formattedPhone = `+${visiblePart} ${hiddenPart}`;
            }
        } catch (error) {
            // If an error occurs, use a default format
            formattedPhone = `+${visiblePart} ${hiddenPart}`;
        }

        return formattedPhone;
    }

    function formatTimestamp(timestamp) {
        var date = new Date(timestamp);
        return date.toLocaleString();
    }
});
