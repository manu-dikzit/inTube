// 2. This code loads the IFrame Player API code asynchronously.
       var tag = document.createElement("script");
       tag.src = "https://www.youtube.com/iframe_api";
       var firstScriptTag = document.getElementsByTagName("script")[0];
       firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
     // 3. This function creates an <iframe> (and YouTube player)
     //    after the API code downloads.
       var player = new Array;
      // 4. The API will call this function WHEREn the video player is ready.
       //
       // Load suggestions for this video
      var suggestionsLoaded = 0;
       jQuery(document).ready(function(e) {
          for(var videoCount = 0; videoCount < videosArray.length; videoCount++){
                 getSuggestions("manyu",videosArray[videoCount]);
          } 
       });
       function onYouTubeIframeAPIReady() {
              for(var videoCount = 0;videoCount  < videosArray.length; videoCount ++){
                   player[videoCount] = new YT.Player("ytplayer"+videoCount, {
                                               height: "400",
                                               width: "400",
                                               videoId: videosArray[videoCount],
                                               events: {
                                                    "onReady": onPlayerReady,
                                                    "onStateChange": onPlayerStateChange
                                                    },
                                               playerVars: {rel: 0, enablejsapi: 1}
                          });
                }
       }

       function onPlayerReady(event) {
         if(event.target.a.id == 'ytplayer0'){

                  setTimeout(function(){
                       var width = event.target.a.clientWidth;
                       var height = event.target.a.clientHeight;
                       jQuery("#suggestionsContainer0").css({"position":"absolute","background":"black","opacity":"1"});
                       jQuery("#suggestionsContainer0").width(width);
                       jQuery("#suggestionsContainer0").height(height - 70);
                       var position=jQuery('#'+event.target.a.id).offset();
                       var k =Math.floor(suggestion_row(width));
                       var l =Math.floor(suggestion_column(height));
                       var i = 12/k ;
                        console.log(k);
                       var align_height = (height - (l*106))/2;
                       var align_width = (width- (k*155))/2;
                         console.log(align_width);
                       console.log(position.left);
                       var suggestiontop = position.top + align_height - 10;
                        var suggestionIndex = 0;
                      for(var counter1=0;counter1<l;counter1++){
                            var divtest = document.createElement("div");
                            jQuery(divtest).addClass("row-fluid");
                             jQuery(divtest).css("margin-bottom","4px");
                             jQuery(divtest).appendTo("#suggestionsContainer0");
                      for(counter2=0;counter2<k;counter2++){
                                   var span = document.createElement("div");
                                   jQuery(span).addClass("span"+ i);
                                   jQuery(span).appendTo(divtest);
                                   var link = document.createElement("a");
  							   if(suggestions[suggestionIndex] != null){
									   link.href = suggestions[suggestionIndex]["link"];
									   var image = document.createElement("img");
									   //image.src="http://placehold.it/155x106";
									   image.src = suggestions[suggestionIndex]["thumbnail"];
									   suggestionIndex++;
									   jQuery(image).appendTo(link);
									   jQuery(link).appendTo(span);
								   }
                         }
            } 
   
                 jQuery("#suggestionsContainer0").offset({ top: suggestiontop, left:position.left});
   },5000); 
         } 
               if(event.target.a.id == 'ytplayer0'){
                     event.target.playVideo();
               }
                }
      // 5. The API calls this function when the players state changes.
      //    The function indicates that when playing a video (state=1),
      //    the player should play for six seconds and then stop.
       function onPlayerStateChange(event) {
                       if (event.data == 0) {
                              console.log("Ended");
                              var width = event.target.a.clientWidth;
                              var height = event.target.a.clientHeight;
                              jQuery('#suggestionsContainer0').children().remove();
                              jQuery("#suggestionsContainer0").css({"position":"absolute","background":"black","opacity":"1"});
                              jQuery("#suggestionsContainer0").width(width);
                              jQuery("#suggestionsContainer0").height(height - 70);
                              var position=jQuery('#'+event.target.a.id).offset();
                              var k =Math.floor(suggestion_row(width));
                              var l =Math.floor(suggestion_column(height));
                              var i = 12/k ;
                              console.log(k);
                              var align_height = (height - (l*106))/2;
                              var align_width = (width- (k*155))/2;
                              console.log(align_width);
                              console.log(position.left);
                              var suggestiontop = position.top + align_height - 10;
                              var suggestionIndex = 0;
                              for(var counter1=0;counter1<l;counter1++){
                                    var divtest = document.createElement("div");
                                     jQuery(divtest).addClass("row-fluid");
                                    jQuery(divtest).css("margin-bottom","4px");
                                    jQuery(divtest).appendTo("#suggestionsContainer0");
                                    for(counter2=0;counter2<k;counter2++){
                                            var span = document.createElement("div");
                                           jQuery(span).addClass("span"+ i);
                                           jQuery(span).addClass("suggestion");
                                           jQuery(span).attr('id','suggestion'+suggestionIndex);
                                           jQuery(span).appendTo(divtest);
										   if(suggestions[suggestionIndex] != null){
											   var link = document.createElement("a");
											   link.href = suggestions[suggestionIndex]["link"];
											   var image = document.createElement("img");
											   image.src = suggestions[suggestionIndex]["thumbnail"];
											   image.id = suggestionIndex;
											   suggestionIndex++;
												jQuery(image).appendTo(link);
												 jQuery(link).appendTo(span);
										   }
                              }
                              jQuery('.suggestion').click(function(){
                                  window.location = suggestions[this.id.substring(10)]['link'];
                              });
                              jQuery('.suggestion').hover(function(){
                                                      jQuery(this).css({background: 'grey'});
                                                      this.children[0].children[0].style.opacity = 0.5;
                                                      if(jQuery('#suggestionText').length < 1){
                                                          var suggestionIndex = this.children[0].children[0].id;
                                                          var suggestionTitle = suggestions[suggestionIndex]['title'];
                                                          var suggestionText = document.createElement('div');
                                                          suggestionText.id = "suggestionText";
                                                          jQuery(suggestionText).text(suggestionTitle);
                                                          this.appendChild(suggestionText);
                                                      }
                                                     },function(){
                                                       jQuery(this).css({background: 'black'});
                                                       jQuery('#suggestionText').remove();
                                                     });
                       }
                          //    jQuery("#suggestionsContainer0").offset({ top: suggestiontop, left:position.left});
                        jQuery('#suggestionsContainer0').css({marginLeft : 0, marginTop: 0, top : suggestiontop , left : position.left});       
                       jQuery('.suggestions').show();
                           }
                       else if(event.data == 1){
                              console.log("playing");
                              jQuery('.suggestions').hide();
                           }
                     }     
