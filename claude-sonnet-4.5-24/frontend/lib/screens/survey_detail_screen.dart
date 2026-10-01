import 'package:flutter/material.dart';
import 'package:graphql_flutter/graphql_flutter.dart';

const String surveyQuery = r'''
  query GetSurvey($surveyId: Int!) {
    survey(surveyId: $surveyId) {
      id
      title
      description
      questions {
        id
        questionText
        questionType
        order
        required
      }
    }
  }
''';

const String addQuestionMutation = r'''
  mutation AddQuestion($surveyId: Int!, $questionText: String!, $questionType: String!, $order: Int!, $required: Boolean!) {
    addQuestion(surveyId: $surveyId, questionInput: {questionText: $questionText, questionType: $questionType, order: $order, required: $required}) {
      id
      questionText
    }
  }
''';

class SurveyDetailScreen extends StatelessWidget {
  final int surveyId;
  final String surveyTitle;

  const SurveyDetailScreen({
    super.key,
    required this.surveyId,
    required this.surveyTitle,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(surveyTitle),
      ),
      body: Query(
        options: QueryOptions(
          document: gql(surveyQuery),
          variables: {'surveyId': surveyId},
        ),
        builder: (QueryResult result, {refetch, fetchMore}) {
          if (result.hasException) {
            return Center(child: Text('Error: ${result.exception.toString()}'));
          }

          if (result.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final survey = result.data?['survey'];
          final questions = survey?['questions'] as List? ?? [];

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          survey?['title'] ?? 'Untitled',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        const SizedBox(height: 8),
                        Text(survey?['description'] ?? 'No description'),
                      ],
                    ),
                  ),
                ),
              ),
              Expanded(
                child: questions.isEmpty
                    ? const Center(child: Text('No questions yet'))
                    : ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: questions.length,
                        itemBuilder: (context, index) {
                          final question = questions[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              leading: CircleAvatar(child: Text('${index + 1}')),
                              title: Text(question['questionText']),
                              subtitle: Text('Type: ${question['questionType']}'),
                              trailing: question['required']
                                  ? const Chip(
                                      label: Text('Required'),
                                      backgroundColor: Colors.red,
                                      labelStyle: TextStyle(color: Colors.white, fontSize: 10),
                                    )
                                  : null,
                            ),
                          );
                        },
                      ),
              ),
            ],
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          _showAddQuestionDialog(context);
        },
        icon: const Icon(Icons.add),
        label: const Text('Add Question'),
      ),
    );
  }

  void _showAddQuestionDialog(BuildContext context) {
    final titleController = TextEditingController();
    String questionType = 'text';
    bool required = false;

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => Mutation(
          options: MutationOptions(
            document: gql(addQuestionMutation),
            onCompleted: (dynamic resultData) {
              Navigator.of(context).pop();
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Question added!')),
              );
            },
          ),
          builder: (runMutation, result) {
            return AlertDialog(
              title: const Text('Add Question'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: titleController,
                      decoration: const InputDecoration(
                        labelText: 'Question Text',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 2,
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      value: questionType,
                      decoration: const InputDecoration(
                        labelText: 'Question Type',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'text', child: Text('Text')),
                        DropdownMenuItem(value: 'multiple_choice', child: Text('Multiple Choice')),
                        DropdownMenuItem(value: 'checkbox', child: Text('Checkbox')),
                        DropdownMenuItem(value: 'rating', child: Text('Rating')),
                      ],
                      onChanged: (value) {
                        setState(() => questionType = value!);
                      },
                    ),
                    const SizedBox(height: 16),
                    SwitchListTile(
                      title: const Text('Required'),
                      value: required,
                      onChanged: (value) {
                        setState(() => required = value);
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: result?.isLoading ?? false
                      ? null
                      : () {
                          if (titleController.text.isNotEmpty) {
                            runMutation({
                              'surveyId': surveyId,
                              'questionText': titleController.text,
                              'questionType': questionType,
                              'order': 0,
                              'required': required,
                            });
                          }
                        },
                  child: result?.isLoading ?? false
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Add'),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}