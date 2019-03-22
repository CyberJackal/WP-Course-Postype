( function( $ ) {

    $( document ).ready(function() {

        var course_instance_count = $('#session-table tbody tr.course-instance').length;
        var objective_row_count = $('#objective-table tbody tr').length;


        $(document).on('click', '.add-instance', function(e){
            e.preventDefault();

            var html = '<tr class="course-instance" data-course-instance="'+course_instance_count+'">' +
                '<td>' +
                    '<input type="hidden" name="instance['+course_instance_count+'][ID]" value="" class="session_id_field" />' +
                    '<input type="date" name="instance['+course_instance_count+'][start_date]" value="" />' +
                '</td>' +
                '<td>' +
                    '<input type="date" name="instance['+course_instance_count+'][end_date]" value="" />' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="instance['+course_instance_count+'][location]" class="regular-text" value="" />' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="instance['+course_instance_count+'][price]" value="" />' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="instance['+course_instance_count+'][external_link]" value="" />' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<input type="checkbox" class="multi-session-box" name="instance['+course_instance_count+'][multi_session]" value="1" />' +
                '</td>' +
                '<td>' +
                    '<a href="#" class="delete-instance">X</a>' +
                '</td>' +
            '</tr>';

            $('#session-table > tbody').append( html );

            course_instance_count++;
        });

        $(document).on('change', '.multi-session-box', function(e){
          var $row = $(this).closest('tr');
          var instance = $row.data('course-instance');
          if( $(this).prop("checked") ){
            $( '<tr class="course-sessions" data-course-instance="'+instance+'">' +
            '<td></td>' +
            '<td colspan="6">'+
              '<table class="course-sessions">'+
                '<thead><tr>'+
                '<th>Session Date</th>'+
                '<th>Session Length</th>'+
                '<th></th>'+
                '</tr></thead>'+
                '<tbody><tr class="course-session" data-course-sessions="0">'+
                  '<td><input type="text" name="instance['+instance+'][sessions][0][date]" /></td>'+
                  '<td><input type="text" name="instance['+instance+'][sessions][0][length]" /></td>'+
                  '<td><a href="#" class="remove-corse-session">X</a></td>'+
                '</tr></tbody>'+
                '<tfoot><tr><td colspan="3"><a href="#" class="add-session">Add session</a></td></tr></tfoot>'+
              '</table>'+
            '</td>' +
            '</tr>' ).insertAfter( $row );
          }else{
            $row.next('.course-sessions').remove();
          }
        });

        $(document).on('click', '.add-session', function(e){
          e.preventDefault();
          var $table = $(this).closest('.course-sessions');
          var $row = $table.closest('tr');
          var instance = $row.data('course-instance');
          var session_count = $table.find('tbody > tr').last().data('course-sessions') + 1;
          if( !session_count ){
            session_count = 0;
          }

          $table.find('tbody').append( '<tr class="course-session" data-course-sessions="'+session_count+'">'+
            '<td><input type="text" name="instance['+instance+'][sessions]['+session_count+'][date]" /></td>'+
            '<td><input type="text" name="instance['+instance+'][sessions]['+session_count+'][length]" /></td>'+
            '<td><a href="#" class="remove-corse-session">X</a></td>'+
          '</tr>' );
        });

        $(document).on('click', '.remove-corse-session', function(e){
          e.preventDefault();
          $(this).closest('.course-session').remove();
        });

        $(document).on('click', '.add-objective', function(e){
            e.preventDefault();

            var  html = '<tr>' +
                '<td>' +
                    '<label>Objective Title</label><br />' +
                    '<input type="text" name="objective['+objective_row_count+'][title]" style="width:100%;" />' +
                    '<br />' +
                    '<label>Objective Description</label><br />' +
                    '<textarea name="objective['+objective_row_count+'][desc]" style="width:100%;" rows="6"></textarea>' +
                '</td>' +
                '<td style="vertical-align:top;padding-top:24px;text-align:center;">' +
                    '<a href="#" class="delete-objective">X</a>' +
                '</td>' +
            '</tr>';

            $('#objective-table tbody').append( html );

            objective_row_count++;
        });

        $(document).on('click', '.delete-instance', function(e){
            e.preventDefault();

            var $row = $(this).closest('tr')
            var session_id = $row.find('.session_id_field').val();

            data = {
                'action': 'lh_remove_session_from_course',
                'session_id': session_id
            }

            $.ajax({
                url: lh_course_ajax_object.ajax_url,
                type: 'post',
                data: data,
                success: function( html ) {
                    $row.remove();
                }
            });


        });

    });

} )( jQuery );
