jQuery(document).ready(function($) {
  // Tab Switching
  $('.aba-nav-tabs .nav-tab').on('click', function(e) {
    if($(this).attr('href').indexOf('&tab=') !== -1) {
      e.preventDefault();
      $('.aba-nav-tabs .nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      
      var tabId = $(this).attr('href').split('&tab=')[1];
      $('.aba-tab-content').removeClass('active');
      $('#tab-' + tabId).addClass('active');
      
      // Update URL without reload
      var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=ai-blog-automator-settings&tab=" + tabId;
      window.history.pushState({path:newUrl}, '', newUrl);
    }
  });

  // Password Toggle
  $('.aba-toggle-password').on('click', function() {
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
  $('#aba-ai-provider').on('change', function() {
    if ($(this).val() === 'groq') {
      $('#aba-gemini-card').hide();
      $('#aba-groq-card').show();
    } else {
      $('#aba-gemini-card').show();
      $('#aba-groq-card').hide();
    }
  });
});
