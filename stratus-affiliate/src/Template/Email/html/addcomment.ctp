 <?php echo $this->element('emailHeader'); ?>
				<table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="font-family: Georgia, serif; background: #fff;" bgcolor="#ffffff">
			      <tr>
			        <td width="14" style="font-size: 0px;" bgcolor="#ffffff">&nbsp;</td>
					<td width="100%" valign="top" align="left" bgcolor="#ffffff" style="font-family: Georgia, serif; background: #fff;">
						<table cellpadding="0" cellspacing="0" border="0"  style="color: #333333; font: normal 13px Arial; margin: 0; padding: 0;" width="100%" class="content">
						<!-- <tr>
							<td style="padding: 25px 0 5px; border-bottom: 2px solid #d2b49b;font-family: Georgia, serif; "  valign="top" align="center">
								<h3 style="color:#767676; font-weight: normal; margin: 0; padding: 0; font-style: italic; line-height: 13px; font-size: 13px;">~ <currentmonthname> <currentday>, <currentyear> ~</h3>
							</td>
						</tr> -->
						<tr>
							<td style="padding: 18px 0 0;" align="left">
								<h2 style=" font-weight: normal; margin: 0; padding: 0 0 12px; font-style: inherit; line-height: 30px; font-size: 25px; font-family: Trebuchet MS; border-bottom: 1px solid #333333; "> <?php echo __d('user','Some one commented on your product - Product ID');?> #<?php echo $itemid; ?></h2>
							</td>
						</tr>

							<tr>
								<td style="padding: 15px 0;"  valign="top">
									<p style='margin-bottom: 10px'>
										 <?php echo __d('user','Hello').' '.$name; ?>,
									</p>
									<p style='margin-bottom: 10px'>
		<?php echo __d('user','There is a user commented on your product');?> #<?php echo $itemid; ?>

									</p>
									<p style='margin-bottom: 10px'>
<?php echo __d('user','Link to your product');?>: <?php echo '<a href="'.SITE_URL.'listing/'.$itemurl.'">'.$itemname.'</a>'; ?>

									</p>
									<p style='margin-bottom: 10px'>
	<?php echo __d('user','Comment');?>:
									</p>
<p style="background-color: #f1f1f1; font-style: italic; margin-bottom: 10px; padding: 10px;">
<?php echo $comments; ?>


									</p>

								</td>
							</tr>

							<tr>
								<td style="padding: 15px 0"  valign="top">
									<p style="color: #333333; font-weight: normal; margin: 0; padding: 0; line-height: 20px; font-size: 14px;font-family: Arial; ">
										<?php echo __d('user','Regards');?>,
										<br />
										<b><?php echo $setngs['site_name'].' '.__d('user','Team'); ?>.</b>
									</p>
									<br>
								</td>
							</tr>
						</table>
					</td>
					<td width="16" bgcolor="#ffffff" style="font-size: 0px;font-family: Georgia, serif; background: #fff;">&nbsp;</td>
			      </tr>
				</table><!-- body -->
				  <?php echo $this->element('emailFooter'); ?>
		  	</td>
		</tr>
    </table>
  </body>
</html>
<?php //die; ?>

