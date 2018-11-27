package services;

import models.Book;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import utilities.ConnectionMySQL;
import utilities.GoogleBookAPI;
import utilities.JsonToBook;

import javax.jws.WebService;
import java.sql.*;
import java.util.HashMap;
import java.util.Map;

import static utilities.ConnectionMySQL.closeConnection;
import static utilities.ConnectionMySQL.getConnection;

@WebService()
public class BookServiceImpl implements BookService {
    private static Map<String, Book> books = new HashMap<String, Book>();
    
    
    @Override
    public Book getBook(String bookID) {
        GoogleBookAPI googleBookAPI = new GoogleBookAPI("id:" + bookID);
        System.out.println("id:" + bookID);
        JSONObject semiResult = googleBookAPI.searchBook();
        
        try {
            JSONArray arrayResult = new JSONArray(semiResult.get("items").toString());
            JSONObject hasilJSON = new JSONObject(arrayResult.get(0).toString());
            JsonToBook translator = new JsonToBook();
            Book book = translator.translateToBook(hasilJSON);
            book.setBookPrice(getPrice(bookID));
            return book;
        } catch (JSONException err) {
            System.out.println(err);
        }
        return null;
    }
    
    /**
     * Get price from database. If exist, return the price. If doesn't exist, return -1
     *
     * @param id
     * @return Integer price
     */
    protected Integer getPrice(String id) {
        try {
            String query = "SELECT price FROM book WHERE idbook = (?);";
            Connection con = getConnection();
        
            PreparedStatement p = con.prepareStatement(query);
            p.setString(1, id);
    
            ResultSet resultSet = p.executeQuery();
    
            Integer price;
            if(resultSet.next()){
                price = resultSet.getInt("price");
            } else {
                price = -1;
            }
            
            closeConnection(con);
            
            return price;
            
        } catch (SQLException | ClassNotFoundException e) {
            e.printStackTrace();
        }
        return 0;
    }
    
    @Override
    public Book[] searchBook(String query) {
        GoogleBookAPI googleBookAPI = new GoogleBookAPI(query);
        JSONObject hasilJSON = googleBookAPI.searchBook();
        try {
            JSONArray resultItems = new JSONArray(hasilJSON.get("items").toString());
            Book[] bookResults = new Book[resultItems.length()];
            
            for (int i = 0; i < Math.min(10, resultItems.length()); i++) {
                JsonToBook translator = new JsonToBook();
                Book bookResult = translator.translateToBook(new JSONObject(resultItems.get(i).toString()));
                bookResults[i] = bookResult;
                if (i < 5) {
                    Boolean isSuccess = insertBook(bookResult);
                }
            }
            return bookResults;
        } catch (JSONException err) {
            System.out.println(err);
        }
        
        
        return null;
    }
    
    /**
     * Insert book to database. Return true if success, false if failed
     *
     * @param book
     * @return boolean
     */
    protected Boolean insertBook(Book book) {
        
        try {
            String query = "INSERT INTO book VALUES (?, ?, ?, ? );";
            Connection con = getConnection();

            PreparedStatement p = con.prepareStatement(query);
            p.setString(1, book.getBookID());
            
            Array categories = con.createArrayOf("varchar", book.getCategories());
            p.setArray(2, categories);
            p.setInt(3, book.getBookPrice());
            p.setInt(4,0);
            
            p.execute();

            closeConnection(con);

            return true;
        } catch (SQLException e) {
            e.printStackTrace();
        } catch (ClassNotFoundException e) {
            e.printStackTrace();
        }
    
        return false;
    }
}
    