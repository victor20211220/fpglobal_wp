jQuery(document).ready(function ($) {
    $.ajax({
        url: periskope_ajax_obj.ajax_url,
        method: 'GET',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', periskope_ajax_obj.nonce);
        },
        success: function (data) {
            if (data === 'No messages found') {
                $('#chat-container').html('<p>No messages found</p>');
                return;
            }

            var chatContainer = $('#chat-container');
            chatContainer.empty();
            var storageDomain = "https://wvtpktbhobaopdiawdli.supabase.co/";

            data.forEach(function (row) {
                var messageClass = row.from_me == 1 ? 'sent' : 'received';
                var mediaContent = '';

                // Parse and format the author phone number
                var author = formatPhoneNumber(row.author);
                var timestamp = formatTimestamp(row.timestamp);

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
                    <div class="message ${messageClass}">
                        <div class="author">${author}</div>
                        <div class="body">${row.body}</div>
                        ${mediaContent}
                        <div class="timestamp">${timestamp}</div>
                    </div>
                `;
                chatContainer.append(message);
            });
        },
        error: function (error) {
            console.log(error);
        }
    });

    function formatPhoneNumber(phone) {
        // Remove '@c.us'
        phone = phone.replace('@c.us', '');

        // Hide the last four digits
        var visiblePart = phone.slice(0, -4);
        var hiddenPart = '****'; //****

        // Use the libphonenumber-js library to format the number
        var formattedPhone = '';

        try {
            // Parse the phone number using libphonenumber-js
            var phoneNumber = libphonenumber.parsePhoneNumberFromString(phone, 'International');
            if (phoneNumber) {
                // Get the country calling code and national number
                var countryCallingCode = phoneNumber.countryCallingCode;
                var nationalNumber = phoneNumber.nationalNumber;

                // Format the national number with spaces
                var formattedNationalNumber = nationalNumber.replace(/(\d{1,4})(?=(\d{2,3})+(?!\d))/g, '$1 ');

                // Construct the formatted phone number
                formattedPhone = `+${countryCallingCode} ${formattedNationalNumber} ${hiddenPart}`;
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

    formatTimestamp = (timestamp) => {

        // Create a Date object from the timestamp
        date = new Date(timestamp);

        // Format the date to a local date string
        localDate = date.toLocaleDateString();
        localTime = date.toLocaleTimeString();
        localDateTime = date.toLocaleString();
        return localDateTime;
    }
});
