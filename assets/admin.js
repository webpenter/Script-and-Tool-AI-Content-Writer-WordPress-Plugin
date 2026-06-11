jQuery(document).ready(function($) {
  // Tab Switching
  $('.abm-nav-tabs .nav-tab').on('click', function(e) {
    if($(this).attr('href').indexOf('&tab=') !== -1) {
      e.preventDefault();
      $('.abm-nav-tabs .nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      
      var tabId = $(this).attr('href').split('&tab=')[1];
      $('.abm-tab-content').removeClass('active');
      $('#tab-' + tabId).addClass('active');
      
      // Update URL without reload
      var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=ai-blog-master-settings&tab=" + tabId;
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

  // Test API Connection
  $('.abm-test-api').on('click', function() {
    var btn = $(this);
    var provider = btn.data('provider');
    var apiKeyInput = btn.closest('.abm-card').find('input[type="password"], input[type="text"]');
    var apiKey = apiKeyInput.val();
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
