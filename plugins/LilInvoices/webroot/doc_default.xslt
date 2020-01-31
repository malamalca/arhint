<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:decimal-format name="dec" decimal-separator="," grouping-separator="."/>
	<xsl:param name="header" />
	<xsl:param name="footer" />

	<xsl:template match="/IzdaniDokumenti/Dokument">
		<xsl:variable name="naslov" select="Naslov"/>
		<xsl:variable name="tip" select="Tip"/>
		<xsl:variable name="stevilka" select="Stevilka"/>
		<xsl:variable name="izdajatelj" select="Izdajatelj"/>

		<html>
			<head>
                <meta name="viewport" content="width=device-width, user-scalable=no initial-scale=1.0, minimum-scale=1.0" />
				<title>
					<xsl:value-of select="concat('Dokument [ ', $naslov,' ] - št.: ', $stevilka)"/>
				</title>
				<style type="text/css">
                    #folding-line {
                        position: absolute;
                        top: 7cm;
                        left: 0cm;
                        width: 1cm;
                        border-top: solid 1px #000;
                    }
                    td#narocnik-wrapper{
                        height: 5.5cm;
                        padding-top: 1.5cm;
                        vertical-align: top;
                    }
                    table.narocnik .posta_kraj {
                        padding-top: 0.5cm;
                    }

                    .bold { font-weight: bold; }
                </style>
			</head>
			<body>
				<xsl:variable name="narocnik" select="PodatkiPodjetja[NazivNaslovPodjetja/VrstaPartnerja='IV']"/>
				<xsl:variable name="narocnik_naziv_1" select="concat($narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja1, ' ', $narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja2)"/>
				<xsl:variable name="narocnik_naziv_2" select="concat($narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja3, ' ', $narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja4)"/>
				<xsl:variable name="narocnik_naslov_1" select="concat($narocnik/NazivNaslovPodjetja/Ulica/Ulica1, $narocnik/NazivNaslovPodjetja/Ulica/Ulica2)"/>
				<xsl:variable name="narocnik_naslov_2" select="concat($narocnik/NazivNaslovPodjetja/Ulica/Ulica3, $narocnik/NazivNaslovPodjetja/Ulica/Ulica4)"/>
				<xsl:variable name="narocnik_posta_kraj" select="concat($narocnik/NazivNaslovPodjetja/PostnaStevilka, ' ', $narocnik/NazivNaslovPodjetja/Kraj)"/>
				<xsl:variable name="narocnik_drzava" select="concat($narocnik/NazivNaslovPodjetja/KodaDrzave, ' - ', $narocnik/NazivNaslovPodjetja/NazivDrzave)"/>
				<xsl:variable name="narocnik_davcna" select="$narocnik/ReferencniPodatkiPodjetja[VrstaPodatkaPodjetja='VA']/PodatekPodjetja"/>
				<xsl:variable name="narocnik_maticna" select="$narocnik/ReferencniPodatkiPodjetja[VrstaPodatkaPodjetja='GN']/PodatekPodjetja"/>

				<xsl:variable name="referencni_dokumenti" select="ReferencniDokumenti"/>
				<xsl:variable name="kraj_izdaje" select="NazivLokacije"/>
				<xsl:variable name="datum_izdaje" select="Datum"/>
				<xsl:variable name="besedilo" select="Besedilo"/>

                <div id="folding-line"></div>
				<div id="print-area">
				    <div id="header2"><xsl:value-of select="$header" disable-output-escaping="yes" /></div>
				    <div id="content">
    				<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
    						<td>
    							<table width="70%" class="basics" cellpadding="0" cellspacing="0">
                                    <tr>
    									<td width="40%">Datum:</td>
    									<td width="60%">
    										<xsl:call-template name="format_date">
    											<xsl:with-param name="date" select="$datum_izdaje"/>
    										</xsl:call-template>
    									</td>
    								</tr>
    								<tr>
    									<td width="40%">Kraj:</td>
    									<td width="60%"><xsl:value-of select="$kraj_izdaje"/></td>
    								</tr>
    								<tr>
    									<td width="40%">Št. dokumenta:</td>
    									<td width="60%"><xsl:value-of select="$stevilka"/></td>
    								</tr>
    							</table>
    						</td>
    					</tr>
                      <tr>
                        <td id="narocnik-wrapper">
                            <br /><br /><br />
                            <table width="70%" class="narocnik" cellpadding="0" cellspacing="0">
								<tr>
									<td colspan="2"><xsl:value-of select="$narocnik_naziv_1"/></td>
								</tr>
								<xsl:if test="string-length($narocnik_naziv_2) &gt; 0">
									<tr>
										<td colspan="2"><xsl:value-of select="$narocnik_naziv_2"/></td>
									</tr>
								</xsl:if>
								<tr>
									<td colspan="2"><xsl:value-of select="$narocnik_naslov_1"/></td>
								</tr>
								<xsl:if test="string-length($narocnik_naslov_2) &gt; 0">
									<tr>
                                        <td>
                                          <xsl:value-of select="$narocnik_naslov_2"/>
                                        </td>
									</tr>
								</xsl:if>
								<tr>
									<td colspan="2" class="posta_kraj"><xsl:value-of select="$narocnik_posta_kraj"/></td>
								</tr>
								<tr>
									<td colspan="2"><xsl:value-of select="$narocnik_drzava"/></td>
								</tr>
								<tr>
									<td colspan="2" height="30px"/>
								</tr>
							</table>
                        </td>
					</tr>
					<tr>
						<td height="50px"/>
					</tr>
				</table>
				    <h1>
							<xsl:value-of select="$naslov" disable-output-escaping="yes"/>
						</h1>
				    <xsl:value-of select="$besedilo" disable-output-escaping="yes"/>
                    </div>
                    <div id="footer2"><xsl:value-of select="$footer" disable-output-escaping="yes" /></div>
                </div>
			</body>
		</html>
	</xsl:template>

	<xsl:template name="Parameter">
		<xsl:param name="name"/>
		<xsl:for-each select="PoljubnoBesedilo/Besedilo[Tekst1 = $name]">
			<xsl:value-of select="concat(Tekst2, Tekst3, Tekst4, Tekst5)"/>
		</xsl:for-each>
	</xsl:template>

	<xsl:template name="format_date">
		<xsl:param name="date"/>
		<xsl:value-of select="concat(substring($date, 9, 2), '.', substring($date, 6, 2), '.', substring($date, 1, 4))"/>
	</xsl:template>

	<xsl:template match="text()"/>
</xsl:stylesheet>
