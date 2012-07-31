<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<title>QRpedia - Language-detecting &amp; mobile-friendly Wikipedia QR codes</title>
		<link href="css/qrpedia.css" rel="stylesheet" type="text/css">
	</head>

	<body>
		<div id="wrapper">
			<div id="inner_wrapper">
				<div id="title">
					<a href="http://en.wikipedia.org/wiki/QRpedia" target="_blank" class="title">What Is QRpedia?</a>
				</div>
				<div id="panel">
				
					<div id="panel_content">
						<div id="qr_area">
							<div id="image">
								<a id="download" class="download" >
									<img class="qr" id="qr" src="images/welcome_qr.png" width="345" height="345" alt="Paste a Wikipedia URL into the box below to create a language-detecting mobile-friendly QR code" />
								</a>
							</div>
						</div>
						
						<div id="url_area">
							<form id="target" action="/" method="get">
								<textarea class="url" id="url" rows="2" autofocus placeholder="Paste your Wikipedia URL here"></textarea>
							</form>
						</div>
						
					</div>
					
				</div>
				
				<div id="language_area" class="language_area">&nbsp;
				</div>
				
				<div id="statistics_area" class="statistics_area">&nbsp;
				</div>
						
				<div id="bottom">
					<a href="http://qrpedia.org/blog/" target="_blank">Blog</a> - <a href="http://qrpedia.org/blog/privacy/" target="_blank">Privacy</a> - <a href="http://qrpedia.org/blog/credits/" target="_blank">Credits</a>
				</div>
				
			</div>
		</div>
		<div style="border: 1px solid rgb(0, 0, 0); padding: 10px; display: none; position: absolute; background-color: rgb(238, 238, 238);" id="menu"><a id="download-menu" class="download-menu" >Download QRpedia Code</a></div>
		<script src="http://code.jquery.com/jquery.min.js"></script>
		<script>
		$(document).ready(function(){
		
		});
	
		//	Display the right-click menu	
		$('#qr_area').bind("contextmenu", function(e) {
			$('#menu').css({
				top: e.pageY+'px',
				left: e.pageX+'px'
			}).show();

			return false;
		});

		//	Close the right-click menu
		$(document).ready(function() {

			$('#menu').click(function() {
				$('#menu').hide();
			});
			$(document).click(function() {
				$('#menu').hide();
			});

		});

		//	As a URL is typed or pasted
		$('.url').each(function() {
			// Save current value of element
			$(this).data('oldVal', $(this).val());

			// Look for changes in the value
			$(this).bind("propertychange keyup input paste", function(event){
				// If value has changed...
				if ($(this).data('oldVal') != $(this).val()) {
					// Updated stored value
					$(this).data('oldVal', $(this).val());
	
					var original_URL = $(this).val();
					if (original_URL.indexOf('wikipedia.org/wiki/') > 0)	//	Lazy way to see if it's a Wikipedia URL
					{
						// Form a qrwp URL
						var new_URL = original_URL.replace('wikipedia.org/wiki/','qrwp.org/');
						var new_URL = new_URL.replace('https://','http://');
						
						//	Get the URL path
						var url = document.createElement('a');
						url.href = new_URL;
						var path = url.pathname.replace('/','');
						
						//	Get the language of the article
						var language = url.hostname.replace('.qrwp.org','');
													
						//	Add some text saying how many languages the article has
						//	Call the Wikipedia API	
						$.getJSON(
							'http://'+language+'.wikipedia.org/w/api.php?format=json&callback=?',
							{ 
								'action': 'query', 'prop': 'langlinks', 'lllimit': 500, 'titles': decodeURI(path) 
							}, 
							function(data)
							{ 			
								//	Remove the info text on the page
								$('div.language_area').text('&nbsp;');
								
								//	Find the element by page ID
								for (var pageId in data.query.pages) 
								{
									if (data.query.pages.hasOwnProperty(pageId)) 
									{
										var count = 1;	//	Start at 1 because the API doesn't return the original article in the langlinks
										
										var languages;
										var langlinks = data.query.pages[pageId].langlinks;
										
										// Count how many languages are available.
										for ( languages in langlinks )
										{
											if(langlinks.hasOwnProperty(languages))
											{
												count++;
											}
										}

										//	Add the image to the page
										$('.qr').attr('src','http://qrpedia.org/qr/php/qr.php?size=345&e=L&d='+encodeURI(encodeURI(new_URL)));
						
										//	Add the download link to the page
										$('.download').attr('href','http://qrpedia.org/qr/php/qr.php?size=800&download='+path+'%20QRpedia&e=L&d='+encodeURI(encodeURI(new_URL)));

										//	Add the download link to the right-click menu
										$('.download-menu').attr('href','http://qrpedia.org/qr/php/qr.php?size=800&download='+path+'%20QRpedia&e=L&d='+encodeURI(encodeURI(new_URL)));

										//	Place the text on the page
										$('div.language_area').text('The article will be available in '+count+' languages');
									
										//	Place the text on the page
										$('div.statistics_area').append('<a href="http://qrpedia.org/stats.php?path='+path+'">Statistics</a>');
									}
									
									if (data.query.pages[-1])
									{
										//	Remove the info text on the page
										$('div.language_area').text("Sorry - that doesn't seem to be a valid Wikipedia URL");					
								
									}
								}
							}
						); 
					}
				}
			});
 		});


		
		$("#target").submit(function()
		{
			//	Nothing will happen if enter is pressed on the form.
			return false;
		});
		
		</script>
	</body>
</html>
