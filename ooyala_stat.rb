#!/usr/bin/env ruby -wKU
# encoding: UTF-8
require "digest/sha2"
require "uri"
require "base64"
require "net/http"
require "time"
require "cgi"

class BacklotAPIClient
  def initialize(partner_code, secret_code, cache_for_num_seconds=900)
    @partner_code = partner_code
    @secret_code = secret_code
    @cache_for_num_seconds = cache_for_num_seconds
  end

  def query(querytype,params)
    return send_request(querytype, params)
  end

  private

  def send_request(request_type, params)
    # Round expires to nearest @cache_for_num_seconds interval
    params['expires'] = ((Time.now.to_i + 15).to_f / @cache_for_num_seconds).ceil * @cache_for_num_seconds

    string_to_sign = @secret_code
    url = "http://www.ooyala.com/api/#{request_type}?pcode=#{@partner_code}"

    params.keys.sort.each do |key|
      string_to_sign += "#{key}=#{params[key]}"
      url += "&#{CGI.escape(key.to_s)}=#{CGI.escape(params[key].to_s)}"
    end

    digest = Digest::SHA256.digest(string_to_sign)
    signature = Base64::encode64(digest).chomp.gsub(/=+$/, '')

    url += "&signature=#{CGI.escape(signature)}"
    xml_data = Net::HTTP.get_response(URI.parse(url)).body

    return xml_data;
  end
end
 
class OoyalaStat
  attr_accessor :raw_data, :excluse_it, :include_it
  attr_reader :source_array
  attr_writer :secret, :pcode
  
  def initialize(pcode,secret,exclude_it=nil,include_it=nil)
    @pcode=pcode;
    @secret=secret;
    @exclude_it = exclude_it
    @include_it = include_it
    @file= "Eldotv-VideoMetrics-030310_#{Time.now.strftime('%Y-%m-%d')}.csv"
    client=BacklotAPIClient.new(@pcode,@secret)
    @raw_data = client.query('analytics',{'contentType'=>'video',
                              'date' => "2010-03-03,#{Time.now.strftime('%Y-%m-%d')}",
                              'fields' => 'label,title,plays',
                              'format' => 'csv',
                              'granularity' => 'total',
                              'method' => 'account.videoTotals',
                              'orderBy' => 'plays'}).gsub(',',';')
    @source_array=@raw_data.scan(/([^;]*);([^;]*);([^;]*);([^;]*)\n/)
    # cleaning file from exlude embedCode if exclude_it array is not empty
    if !@exclude_it.nil?
      remove_noise(@source_array,@exclude_it)
    end
    # keep only include embedCode from include_it array
    if !@include_it.nil?
      remove_noise(@source_array,!@include_it)
    end
  end
  
  def save()
    original=File.new(@file,'w')
    puts 'saving file ...'
    original.write(@raw_data)
    original.close
    puts 'done'
  end
  def remove_noise(raw,noise)
      raw.reject! do |row|
        noise.include? row[0]
      end
      raw.map! do |row|
        row.join(';')
      end
      @raw_data=raw.join("\n")
  end
  
end

exclude_array=%w(hha204MTrMf4-QMrn2W40AIy5mviFNt4 lrb2hqMTq4allI9dlmvy4YpQ_hdRfTgd JiNm44MTosns98jV_Z43cVSC0CKWJd6C hubmhqMTqD80Fp50K66kemPmUcrtN_IJ F5d3R4Osv0013nUoCX5DFizoQ84rATBy M0a3NnMTp6wbJcgNlIas4zvJw-R4Bffb psam1iMToAzLJaBMGgUKf82h7yRCJEwy ozd2w4MTobFbIqKyfjahM0X2Wa-kfy0B p3dmNoMTpWwbgEYjdRAI2-HpA-_BgH8m 8ya3NnMTpV-LZPeJug7xSvF6V4utqkkX B2MXU4MToByAPQulszpz11uEzNjQaNA1 R4dmNoMTrrcgQdHt6aFe98y8bhg26HM9 84aWRsMTqohq-m9clLZNj-bN5I6W6I7M BwaGRsMTox5YwJ2dVK7r1_WHFj10KONk N2d3R4OvEzKMJmkeBXjNua1RrhTS1Zrf Jmdmo3MTrEGmK9IlnRI9rDw6OlrHqnH6 Jldmo3MTqyoXU-i8XNi9N5BTwJkRJ2B4 Jia204MTqLNkqikmUXBpbyg_9YFd6DXS 9jdWhqMTq1DwukCmXIseC15tia04wWVW s2bG04MTqmeJQkOjkVxreYd7Z5-NDPWg 9kdWhqMToeM13Wt_FcNclaDtr9XqU78n ZlYmJzMTqZn11aUFup_XBplm6IfA8EXi Q3dDdzMTp_fn49R5yZLkOCixxL2LsPPY NjMmJzMTqCEkpzsxZn1vsdGwyZWky4-O w0Z2JzMTpMqZNskLG6VeD0KZIKTYCKmt FsYWJzMTrgQMaA3VKLEcQZJJsD4wv1cR BiYXZzMTqYeB-TgsdbnScbT2KQ0AdPSb)
test = OoyalaStat.new('A1aWU6gtImdquh3rYc_368uCimNQ','lFtZngJp-k74v1Q9r5aWpY2o2MTt_3S_WbrtQpGf',exclude_array)
test.save