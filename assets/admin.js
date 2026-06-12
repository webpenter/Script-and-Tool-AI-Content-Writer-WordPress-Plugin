jQuery(document).ready(function($) {
  // Tab Switching
  $('.abm-nav-tabs .nav-tab').on('click', function(e) {
    if($(this).attr('href').indexOf('&tab=') !== -1) {
      e.preventDefault();
      $('.abm-nav-tabs .nav-tab').removeClass('active');
      $(this).addClass('active');
      
      var tabId = $(this).attr('href').split('&tab=')[1];
      $('.abm-tab-content').removeClass('active');
      $('#tab-' + tabId).addClass('active');
      
      // Update URL without reload
      var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=webpenter-ai-blog-master-settings&tab=" + tabId;
      window.history.pushState({path:newUrl}, '', newUrl);
    }
  });

  // Password Toggle
  $('.abm-toggle-password').on('click', function() {
    var input = $(this).siblings('input');
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      $(this).text('Hide');
    } else {
      input.attr('type', 'password');
      $(this).text('Show');
    }
  });

  // Provider toggle
  $('#abm-ai-provider').on('change', function() {
    if ($(this).val() === 'groq') {
      $('#abm-gemini-card').hide();
      $('#abm-groq-card').show();
    } else {
      $('#abm-gemini-card').show();
      $('#abm-groq-card').hide();
    }
  });

  // Image Source toggle
  $('#abm-image-source').on('change', function() {
    var source = $(this).val();
    $('.abm-image-key, #abm-ai-style-wrapper').hide();
    
    if (source === 'pixabay') {
      $('#abm-pixabay-key-wrapper').show();
    } else if (source === 'unsplash') {
      $('#abm-unsplash-key-wrapper').show();
    } else if (source === 'huggingface') {
      $('#abm-huggingface-key-wrapper').show();
      $('#abm-ai-style-wrapper').show();
    }
  });

  // Schedule Builder Logic
  function updateScheduleSummary() {
    var frequency = $('#abm-schedule-frequency').val();
    var seconds = parseInt($('#abm-custom-seconds').val()) || 60;
    var summaryText = '';

    if (frequency === 'minutely') {
      summaryText = 'Your engine is set to generate posts <strong>every minute</strong>.';
      $('#abm-custom-interval-wrapper, #abm-presets-row').hide();
    } else if (frequency === 'hourly') {
      summaryText = 'Your engine is set to generate posts <strong>every hour</strong>.';
      $('#abm-custom-interval-wrapper, #abm-presets-row').hide();
    } else if (frequency === 'daily') {
      summaryText = 'Your engine is set to generate posts <strong>every day</strong>.';
      $('#abm-custom-interval-wrapper, #abm-presets-row').hide();
    } else {
      $('#abm-custom-interval-wrapper, #abm-presets-row').show();
      
      var readable = '';
      if (seconds < 60) {
        readable = seconds + ' seconds';
      } else if (seconds < 3600) {
        var mins = Math.floor(seconds / 60);
        var remSecs = seconds % 60;
        readable = mins + ' minute' + (mins > 1 ? 's' : '') + (remSecs > 0 ? ' and ' + remSecs + ' seconds' : '');
      } else {
        var hrs = Math.floor(seconds / 3600);
        var mins = Math.floor((seconds % 3600) / 60);
        readable = hrs + ' hour' + (hrs > 1 ? 's' : '') + (mins > 0 ? ' and ' + mins + ' minute' + (mins > 1 ? 's' : '') : '');
      }
      
      summaryText = 'Your engine is set to generate posts <strong>every ' + readable + '</strong>.';
    }

    $('#abm-schedule-summary-text').html(summaryText);
  }

  $('#abm-schedule-frequency, #abm-custom-seconds').on('change input', updateScheduleSummary);

  $('.abm-preset-btn').on('click', function() {
    var sec = $(this).data('sec');
    $('#abm-custom-seconds').val(sec).trigger('input');
    $('.abm-preset-btn').removeClass('active');
    $(this).addClass('active');
  });

  // Initialize summary on load
  updateScheduleSummary();

  // Test API Connection
  $('.abm-test-api').on('click', function() {
    var btn = $(this);
    var provider = btn.data('provider');
    var apiKey = '';
    
    if (provider === 'image-test') {
      var source = $('#abm-image-source').val();
      if (source === 'pixabay') {
        provider = 'pixabay';
        apiKey = $('input[name="webpenter_abm_settings[pixabay_api_key]"]').val();
      } else if (source === 'unsplash') {
        provider = 'unsplash';
        apiKey = $('input[name="webpenter_abm_settings[unsplash_api_key]"]').val();
      } else if (source === 'huggingface') {
        provider = 'huggingface';
        apiKey = $('input[name="webpenter_abm_settings[huggingface_api_key]"]').val();
      }
    } else {
      apiKey = btn.closest('.abm-card').find('input[type="password"], input[type="text"]').val();
    }

    var resultSpan = btn.siblings('.abm-test-result');

    if (!apiKey) {
      resultSpan.html('<span style="color:red;">Please enter an API Key first.</span>');
      return;
    }

    btn.prop('disabled', true).text('Testing...');
    resultSpan.html('');

    $.ajax({
      url: abm_vars.ajax_url,
      type: 'POST',
      data: {
        action: 'abm_test_api',
        nonce: abm_vars.nonce,
        provider: provider,
        api_key: apiKey
      },
      success: function(response) {
        if (response.success) {
          resultSpan.html('<span style="color:green;">✅ ' + response.data + '</span>');
        } else {
          resultSpan.html('<span style="color:red;">❌ ' + response.data + '</span>');
        }
      },
      error: function() {
        resultSpan.html('<span style="color:red;">❌ Request failed.</span>');
      },
      complete: function() {
        btn.prop('disabled', false).text('Test Connection');
      }
    });
  });
});
