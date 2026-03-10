<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:param name="header"/>
	<xsl:param name="footer"/>

	<xsl:template match="/TravelOrders/TravelOrder">
		<html>
			<head>
				<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, minimum-scale=1.0"/>
				<title>
					<xsl:value-of select="concat('Travel Order ', No, ' - ', Title)"/>
				</title>
				<style type="text/css">
					body {
						font-family: Arial, sans-serif;
						font-size: 8pt;
						color: #111;
						margin: 0;
						padding: 0;
					}
					#print-area {
						padding: 0;
					}
					#header1, #footer1 {
						width: 100%;
					}
					h1.doc-title {
						font-size: 11pt;
						margin: 0.3cm 0 0.2cm 0;
						border-bottom: 2px solid #333;
						padding-bottom: 3px;
					}
					h2.section-title {
						font-size: 9pt;
						margin: 0.4cm 0 0.15cm 0;
						border-bottom: 1px solid #aaa;
						padding-bottom: 2px;
					}
					table.basics {
						width: 100%;
						border-collapse: collapse;
						table-layout: fixed;
					}
					table.basics td {
						padding: 1px 3px;
						vertical-align: top;
						word-wrap: break-word;
					}
					table.basics td.label {
						width: 28%;
						font-weight: bold;
						color: #444;
					}
					table.basics td.value {
						width: 22%;
					}
					table.items {
						width: 100%;
						border-collapse: collapse;
						table-layout: fixed;
						margin-top: 0.15cm;
					}
					table.items th {
						background-color: #e8e8e8;
						border: 1px solid #ccc;
						padding: 2px 3px;
						text-align: left;
						font-size: 7pt;
						word-wrap: break-word;
					}
					table.items th.right {
						text-align: right;
					}
					table.items td {
						border: 1px solid #ddd;
						padding: 2px 3px;
						vertical-align: top;
						font-size: 7pt;
						word-wrap: break-word;
					}
					table.items td.right {
						text-align: right;
					}
					table.items tr.total-row td {
						background-color: #f0f0f0;
						font-weight: bold;
					}
					.descript-block {
						margin-top: 0.2cm;
						padding: 3px 5px;
						background-color: #f9f9f9;
						border: 1px solid #e0e0e0;
					}
					p.doc-subtitle {
						margin: 0 0 0.2cm 0;
						color: #444;
					}
					.draft-watermark {
						position: fixed;
						top: 38%;
						left: 0;
						width: 100%;
						text-align: center;
						-webkit-transform: rotate(-45deg);
						transform: rotate(-45deg);
						font-size: 90pt;
						font-weight: bold;
						color: rgba(180, 0, 0, 0.12);
						white-space: nowrap;
						z-index: 9999;
						pointer-events: none;
						letter-spacing: 0.15em;
					}
				</style>
			</head>
			<body>
				<xsl:if test="Status = 'waiting_approval' or Status = 'waiting_processing'">
					<div class="draft-watermark">DRAFT</div>
				</xsl:if>
				<div id="print-area">
					<xsl:if test="string-length($header) &gt; 0">
						<div id="header1">
							<xsl:value-of select="$header" disable-output-escaping="yes"/>
						</div>
					</xsl:if>

					<div id="content">
						<h1 class="doc-title">
							<xsl:value-of select="concat('Travel Order #', No)"/>
						</h1>

						<table class="basics">
							<tr>
								<td class="label">Date of Issue:</td>
								<td class="value">
									<xsl:call-template name="format_date">
										<xsl:with-param name="date" select="DatIssue"/>
									</xsl:call-template>
								</td>
								<td class="label">Task Date:</td>
								<td class="value">
									<xsl:call-template name="format_date">
										<xsl:with-param name="date" select="DatTask"/>
									</xsl:call-template>
								</td>
							</tr>
							<tr>
								<td class="label">Location:</td>
								<td class="value"><xsl:value-of select="Location"/></td>
								<td class="label">Status:</td>
								<td class="value"><xsl:value-of select="Status"/></td>
							</tr>
							<tr>
								<td class="label">Employee:</td>
								<td class="value"><xsl:value-of select="Employee"/></td>
								<td class="label">Taskee:</td>
								<td class="value"><xsl:value-of select="Taskee"/></td>
							</tr>
							<tr>
								<td class="label">Departure:</td>
								<td>
									<xsl:call-template name="format_datetime">
										<xsl:with-param name="dt" select="Departure"/>
									</xsl:call-template>
								</td>
								<td class="label">Arrival:</td>
								<td>
									<xsl:call-template name="format_datetime">
										<xsl:with-param name="dt" select="Arrival"/>
									</xsl:call-template>
								</td>
							</tr>
							<xsl:if test="string-length(Vehicle/Registration) &gt; 0 or string-length(Vehicle/Title) &gt; 0">
								<tr>
									<td class="label">Vehicle:</td>
									<td colspan="3">
										<xsl:value-of select="Vehicle/Title"/>
										<xsl:if test="string-length(Vehicle/Title) &gt; 0 and string-length(Vehicle/Registration) &gt; 0">
											<xsl:text> – </xsl:text>
										</xsl:if>
										<xsl:value-of select="Vehicle/Registration"/>
										<xsl:if test="string-length(Vehicle/Owner) &gt; 0">
											<xsl:text> (</xsl:text>
											<xsl:value-of select="Vehicle/Owner"/>
											<xsl:text>)</xsl:text>
										</xsl:if>
									</td>
								</tr>
							</xsl:if>
							<xsl:if test="string-length(Advance) &gt; 0">
								<tr>
									<td class="label">Advance:</td>
									<td><xsl:value-of select="Advance"/></td>
									<td class="label">Advance Date:</td>
									<td>
										<xsl:call-template name="format_date">
											<xsl:with-param name="date" select="DatAdvance"/>
										</xsl:call-template>
									</td>
								</tr>
							</xsl:if>
						</table>

						<!-- DESCRIPTION -->
					<xsl:if test="string-length(Title) &gt; 0">
						<h2 class="section-title">Task Description</h2>
						<div class="descript-block">
							<xsl:value-of select="Title"/>
						</div>
					</xsl:if>
					<xsl:if test="string-length(Descript) &gt; 0">
						<h2 class="section-title">Route Description</h2>
						<div class="descript-block">
							<xsl:value-of select="Descript" disable-output-escaping="yes"/>
						</div>
					</xsl:if>

						<!-- APPROVAL / PROCESSING -->
					<xsl:if test="string-length(EnteredBy) &gt; 0 or string-length(ApprovedBy) &gt; 0 or string-length(ProcessedBy) &gt; 0">
						<h2 class="section-title">Workflow</h2>
						<table class="basics">
							<xsl:if test="string-length(EnteredBy) &gt; 0">
								<tr>
									<td class="label">Entered by:</td>
									<td>
										<xsl:value-of select="EnteredBy"/>
										<xsl:if test="string-length(EnteredAt) &gt; 0">
											<xsl:text> – </xsl:text>
											<xsl:call-template name="format_datetime">
												<xsl:with-param name="dt" select="EnteredAt"/>
											</xsl:call-template>
										</xsl:if>
									</td>
								</tr>
							</xsl:if>
								<xsl:if test="string-length(ApprovedBy) &gt; 0">
									<tr>
										<td class="label">Approved by:</td>
										<td>
											<xsl:value-of select="ApprovedBy"/>
											<xsl:if test="string-length(ApprovedAt) &gt; 0">
												<xsl:text> – </xsl:text>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="ApprovedAt"/>
												</xsl:call-template>
											</xsl:if>
										</td>
									</tr>
								</xsl:if>
								<xsl:if test="string-length(ProcessedBy) &gt; 0">
									<tr>
										<td class="label">Processed by:</td>
										<td>
											<xsl:value-of select="ProcessedBy"/>
											<xsl:if test="string-length(ProcessedAt) &gt; 0">
												<xsl:text> – </xsl:text>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="ProcessedAt"/>
												</xsl:call-template>
											</xsl:if>
										</td>
									</tr>
								</xsl:if>
							</table>
						</xsl:if>

						<!-- MILEAGES (completed only) -->
						<xsl:if test="Mileages/Mileage">
							<h2 class="section-title">Mileage</h2>
							<table class="items">
								<thead>
									<tr>
										<th style="width:34%">Route / Description</th>
										<th style="width:15%">Start</th>
										<th style="width:15%">End</th>
										<th class="right" style="width:12%">Distance (km)</th>
										<th class="right" style="width:12%">Price / km</th>
										<th class="right" style="width:12%">Total</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="Mileages/Mileage">
										<tr>
											<td><xsl:value-of select="RoadDescription"/></td>
											<td>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="StartTime"/>
												</xsl:call-template>
											</td>
											<td>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="EndTime"/>
												</xsl:call-template>
											</td>
											<td class="right"><xsl:value-of select="DistanceKm"/></td>
											<td class="right"><xsl:value-of select="PricePerKm"/></td>
											<td class="right"><xsl:value-of select="Total"/></td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</xsl:if>

						<!-- EXPENSES (completed only) -->
						<xsl:if test="Expenses/Expense">
							<h2 class="section-title">Additional Costs</h2>
							<table class="items">
								<thead>
									<tr>
										<th style="width:22%">Description</th>
										<th style="width:11%">Type</th>
										<th style="width:11%">Start</th>
										<th style="width:11%">End</th>
										<th class="right" style="width:7%">Qty</th>
										<th class="right" style="width:8%">Price</th>
										<th style="width:8%">Currency</th>
										<th class="right" style="width:11%">Total</th>
										<th class="right" style="width:11%">Approved</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="Expenses/Expense">
										<tr>
											<td><xsl:value-of select="Description"/></td>
											<td><xsl:value-of select="Type"/></td>
											<td>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="StartTime"/>
												</xsl:call-template>
											</td>
											<td>
												<xsl:call-template name="format_datetime">
													<xsl:with-param name="dt" select="EndTime"/>
												</xsl:call-template>
											</td>
											<td class="right"><xsl:value-of select="Quantity"/></td>
											<td class="right"><xsl:value-of select="Price"/></td>
											<td><xsl:value-of select="Currency"/></td>
											<td class="right"><xsl:value-of select="Total"/></td>
											<td class="right">
												<xsl:choose>
													<xsl:when test="string-length(ApprovedTotal) &gt; 0">
														<xsl:value-of select="ApprovedTotal"/>
													</xsl:when>
													<xsl:otherwise>–</xsl:otherwise>
												</xsl:choose>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</xsl:if>

						<!-- TOTALS (completed only) -->
						<xsl:if test="string-length(Total) &gt; 0">
							<h2 class="section-title">Totals</h2>
							<table class="basics">
								<xsl:if test="string-length(NetTotal) &gt; 0">
									<tr>
										<td class="label">Net Total:</td>
										<td><xsl:value-of select="NetTotal"/></td>
									</tr>
								</xsl:if>
								<tr>
									<td class="label">Total:</td>
								<td><xsl:value-of select="Total"/></td>
							</tr>
							<xsl:if test="string-length(TotalAdvance) &gt; 0">
								<tr>
									<td class="label">Advance:</td>
									<td><xsl:value-of select="TotalAdvance"/></td>
								</tr>
								<tr>
									<td class="label">Total for Payout:</td>
									<td><strong><xsl:value-of select="TotalPayout"/></strong></td>
								</tr>
							</xsl:if>
							<xsl:if test="string-length(TotalAdvance) = 0">
								<tr>
									<td class="label">Total for Payout:</td>
									<td><strong><xsl:value-of select="Total"/></strong></td>
								</tr>
							</xsl:if>
							</table>
						</xsl:if>
					</div>

					<xsl:if test="string-length($footer) &gt; 0">
						<div id="footer1">
							<xsl:value-of select="$footer" disable-output-escaping="yes"/>
						</div>
					</xsl:if>
				</div>
			</body>
		</html>
	</xsl:template>

	<!-- Format a Y-m-d date as d.m.Y -->
	<xsl:template name="format_date">
		<xsl:param name="date"/>
		<xsl:if test="string-length($date) &gt; 9">
			<xsl:value-of select="concat(substring($date,9,2), '.', substring($date,6,2), '.', substring($date,1,4))"/>
		</xsl:if>
	</xsl:template>

	<!-- Format an ISO 8601 datetime: show date + time portion -->
	<xsl:template name="format_datetime">
		<xsl:param name="dt"/>
		<xsl:if test="string-length($dt) &gt; 9">
			<xsl:call-template name="format_date">
				<xsl:with-param name="date" select="$dt"/>
			</xsl:call-template>
			<xsl:if test="string-length($dt) &gt; 10">
				<xsl:text> </xsl:text>
				<xsl:value-of select="substring($dt,12,5)"/>
			</xsl:if>
		</xsl:if>
	</xsl:template>

	<xsl:template match="text()"/>
</xsl:stylesheet>
